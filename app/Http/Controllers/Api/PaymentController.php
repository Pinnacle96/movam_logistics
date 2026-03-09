<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\NotificationService;

class PaymentController extends Controller
{
    protected $paystack;
    protected $notificationService;

    public function __construct(\App\Services\PaystackService $paystack, NotificationService $notificationService)
    {
        $this->paystack = $paystack;
        $this->notificationService = $notificationService;
    }

    /**
     * Initialize payment for an order
     */
    public function initializePayment(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'callback_url' => 'required|url',
        ]);

        $order = Order::findOrFail($request->order_id);

        if ($order->status !== 'pending') {
            return response()->json(['message' => 'Order is already processed'], 400);
        }

        $data = [
            'amount' => (int)($order->total_amount * 100), // Convert to kobo
            'email' => $request->user()->email,
            'reference' => 'PAY-' . strtoupper(uniqid()) . '-' . $order->id,
            'callback_url' => $request->callback_url,
            'metadata' => [
                'order_id' => $order->id,
                'customer_id' => $request->user()->id,
            ]
        ];

        $payment = $this->paystack->initializeTransaction($data);

        if (!$payment) {
            return response()->json(['message' => 'Failed to initialize payment'], 500);
        }

        return response()->json($payment);
    }

    /**
     * Verify payment after Paystack redirect or via webhook
     */
    public function verifyPayment(Request $request)
    {
        $request->validate([
            'reference' => 'required|string',
        ]);

        $paymentData = $this->paystack->verifyTransaction($request->reference);

        if (!$paymentData || $paymentData['status'] !== 'success') {
            return response()->json(['message' => 'Payment verification failed'], 400);
        }

        $orderId = $paymentData['metadata']['order_id'];
        $order = Order::findOrFail($orderId);

        if ($order->payment_status === 'paid') {
            return response()->json(['message' => 'Order already paid', 'order' => $order]);
        }

        return DB::transaction(function () use ($order, $paymentData) {
            // Update order status
            $order->update([
                'payment_status' => 'paid',
                'status' => 'accepted', // Automatically accept once paid
            ]);

            // Credit Platform (Admin) Wallet
            $adminUser = \App\Models\User::role('admin')->first();
            Transaction::create([
                'wallet_id' => $adminUser->wallet->id,
                'amount' => $order->total_amount,
                'type' => 'credit',
                'status' => 'completed',
                'description' => "Platform Payment for Order #{$order->order_number}",
                'reference' => $paymentData['reference'],
            ]);

            // Send Receipt
            $this->notificationService->sendOrderReceipt($order);

            return response()->json([
                'message' => 'Payment verified successfully',
                'order' => $order->load(['merchant', 'items.product'])
            ]);
        });
    }

    /**
     * Handle Paystack Webhooks
     */
    public function handleWebhook(Request $request)
    {
        // Paystack signature verification would go here for production
        $payload = $request->all();

        if ($payload['event'] === 'charge.success') {
            $reference = $payload['data']['reference'];
            
            // Re-verify to be safe
            $paymentData = $this->paystack->verifyTransaction($reference);
            
            if ($paymentData && $paymentData['status'] === 'success') {
                $orderId = $paymentData['metadata']['order_id'];
                $order = Order::find($orderId);

                if ($order && $order->payment_status !== 'paid') {
                    DB::transaction(function () use ($order, $paymentData) {
                        $order->update([
                            'payment_status' => 'paid',
                            'status' => 'accepted',
                        ]);

                        Transaction::create([
                            'wallet_id' => $order->merchant->user->wallet->id,
                            'amount' => $order->total_amount,
                            'type' => 'credit',
                            'status' => 'completed',
                            'description' => "Payment for Order #{$order->order_number}",
                            'reference' => $paymentData['reference'],
                        ]);

                        // Send Receipt
                        $this->notificationService->sendOrderReceipt($order);
                    });
                }
            }
        }

        return response()->json(['status' => 'success']);
    }
}
