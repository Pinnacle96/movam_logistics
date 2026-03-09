<?php

namespace App\Services;

use App\Events\OrderStatusUpdated;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderService
{
    protected $walletService;
    protected $notificationService;
    protected $mapboxService;

    public function __construct(WalletService $walletService, NotificationService $notificationService, MapboxService $mapboxService)
    {
        $this->walletService = $walletService;
        $this->notificationService = $notificationService;
        $this->mapboxService = $mapboxService;
    }

    public function placeOrder(User $customer, array $data): Order
    {
        return DB::transaction(function () use ($customer, $data) {
            $totalAmount = 0;
            $items = [];

            foreach ($data['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);
                $quantity = $item['quantity'];
                
                if ($product->stock < $quantity) {
                    throw new \Exception("Insufficient stock for product: {$product->name}");
                }

                $product->decrement('stock', $quantity);
                
                if ($product->isLowStock()) {
                    $this->notificationService->notifyLowStock($product);
                }

                $price = $product->price;
                $total = $price * $quantity;
                $totalAmount += $total;

                $items[] = [
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'price' => $price,
                    'total' => $total,
                ];
            }

            $merchant = Product::find($data['items'][0]['product_id'])->merchant;
            
            // Calculate dynamic delivery fee if not provided
            $deliveryFee = $data['delivery_fee'] ?? null;
            if ($deliveryFee === null) {
                $distanceKm = $this->mapboxService->getDistance(
                    $merchant->lat ?? 0, 
                    $merchant->lng ?? 0, 
                    $data['delivery_lat'], 
                    $data['delivery_lng']
                );
                
                $baseFee = (float)\App\Models\Setting::get('delivery_fee_base', 1000);
                $perKmFee = (float)\App\Models\Setting::get('delivery_fee_per_km', 200);
                $deliveryFee = $baseFee + ($distanceKm * $perKmFee);
            }

            $commissionRate = $merchant->commission_rate;
            $commissionAmount = $totalAmount * ($commissionRate / 100);
            
            // Apply Coupon if provided
            $discountAmount = 0;
            $couponId = null;
            if (isset($data['coupon_code'])) {
                $coupon = \App\Models\Coupon::where('code', $data['coupon_code'])->first();
                if ($coupon && $coupon->isValidForUser($customer, $totalAmount)) {
                    $discountAmount = $coupon->calculateDiscount($totalAmount);
                    $couponId = $coupon->id;
                    $totalAmount -= $discountAmount;
                }
            }

            $riderShare = $deliveryFee * 0.7;
            $paymentMethod = $data['payment_method'] ?? 'wallet';
            $orderNumber = 'ORD-' . strtoupper(Str::random(10));

            if ($paymentMethod === 'wallet') {
                if ($customer->wallet->balance < ($totalAmount + $deliveryFee)) {
                    throw new \Exception('Insufficient wallet balance');
                }
                $this->walletService->withdraw($customer, $totalAmount + $deliveryFee, "Payment for order #{$orderNumber}");
                
                // Credit Admin (Clearing)
                $adminUser = User::role('admin')->first();
                $this->walletService->deposit($adminUser, $totalAmount + $deliveryFee, "Customer payment for order #{$orderNumber}");
                
                $paymentStatus = 'paid';
            } else {
                $paymentStatus = 'unpaid';
            }

            $order = Order::create([
                'customer_id' => $customer->id,
                'merchant_id' => $merchant->id,
                'coupon_id' => $couponId,
                'order_number' => $orderNumber,
                'total_amount' => $totalAmount + $deliveryFee, // Total includes delivery
                'delivery_fee' => $deliveryFee,
                'commission_amount' => $commissionAmount,
                'discount_amount' => $discountAmount,
                'rider_share' => $riderShare,
                'status' => 'pending',
                'payment_status' => $paymentStatus,
                'payment_method' => $paymentMethod,
                'delivery_address' => $data['delivery_address'],
                'delivery_lat' => $data['delivery_lat'],
                'delivery_lng' => $data['delivery_lng'],
                'pickup_address' => $merchant->address,
                'pickup_lat' => $merchant->lat ?? 0,
                'pickup_lng' => $merchant->lng ?? 0,
            ]);

            if ($couponId) {
                $coupon = \App\Models\Coupon::find($couponId);
                $coupon->increment('used_count');
                $coupon->users()->attach($customer->id, ['used_at' => now()]);
            }

            foreach ($items as $item) {
                $order->items()->create($item);
            }

            if ($paymentStatus === 'paid') {
                $this->notificationService->sendOrderReceipt($order);
            }

            $this->notificationService->notifyOrderStatus($customer, $order->order_number, $order->status);
            $this->notificationService->notifyOrderStatus($merchant->user, $order->order_number, 'new');

            return $order;
        });
    }

    public function updateStatus(Order $order, string $status, ?User $user = null): Order
    {
        return DB::transaction(function () use ($order, $status, $user) {
            if ($user && $user->hasRole('rider') && in_array($status, ['accepted', 'dispatched'])) {
                $order->rider_id = $user->id;
            }

            $order->status = $status;
            $order->save();

            event(new OrderStatusUpdated($order));

            $this->notificationService->notifyOrderStatus($order->customer, $order->order_number, $status);

            if ($status === 'ready') {
                app(DispatchService::class)->dispatchOrder($order);
            }

            if ($status === 'delivered') {
                \App\Jobs\DistributeOrderFunds::dispatch($order);
            }

            return $order;
        });
    }
}
