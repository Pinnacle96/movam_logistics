<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\UserAddress;
use App\Services\OrderService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    protected $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    public function store(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'address_id' => 'nullable|exists:user_addresses,id',
            'delivery_address' => 'required_without:address_id|string',
            'delivery_lat' => 'required_without:address_id|numeric',
            'delivery_lng' => 'required_without:address_id|numeric',
        ]);

        $data = $request->all();

        // If address_id is provided, fetch details
        if ($request->filled('address_id')) {
            $address = UserAddress::where('id', $request->address_id)
                ->where('user_id', $request->user()->id)
                ->firstOrFail();
            
            $data['delivery_address'] = $address->address;
            $data['delivery_lat'] = $address->lat;
            $data['delivery_lng'] = $address->lng;
        }

        try {
            $order = $this->orderService->placeOrder($request->user(), $data);
            return response()->json($order->load('items'), 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $orders = Order::query();

        if ($user->hasRole('customer')) {
            $orders->where('customer_id', $user->id);
        } elseif ($user->hasRole('merchant')) {
            $orders->where('merchant_id', $user->merchant->id);
        } elseif ($user->hasRole('rider')) {
            $orders->where('rider_id', $user->id);
        }

        return response()->json($orders->with(['items', 'merchant', 'rider'])->latest()->paginate(20));
    }

    public function show(Order $order)
    {
        return response()->json($order->load(['items', 'merchant', 'rider']));
    }

    public function updateStatus(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'required|in:accepted,ready,preparing,dispatched,delivered,cancelled',
        ]);

        try {
            $order = $this->orderService->updateStatus($order, $request->status, $request->user());
            return response()->json($order);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}
