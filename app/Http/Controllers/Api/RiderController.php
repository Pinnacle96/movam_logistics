<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Events\RiderLocationUpdated;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;

class RiderController extends Controller
{
    public function dashboard(Request $request)
    {
        $rider = $request->user();

        $stats = [
            'total_deliveries' => Order::where('rider_id', $rider->id)->where('status', 'delivered')->count(),
            'total_earnings' => Order::where('rider_id', $rider->id)->where('status', 'delivered')->sum('rider_share'),
            'active_delivery' => Order::where('rider_id', $rider->id)->whereIn('status', ['accepted', 'preparing', 'dispatched'])->first(),
        ];

        return response()->json([
            'rider' => $rider->load('rider'),
            'stats' => $stats,
        ]);
    }

    public function availableOrders(Request $request)
    {
        // Orders ready for pickup and not yet assigned to a rider
        $orders = Order::where('status', 'ready')
            ->whereNull('rider_id')
            ->with(['merchant', 'items.product'])
            ->latest()
            ->get();

        return response()->json($orders);
    }

    public function myDeliveries(Request $request)
    {
        $rider = $request->user();
        $orders = Order::where('rider_id', $rider->id)
            ->with(['merchant', 'customer', 'items.product'])
            ->latest()
            ->paginate(15);

        return response()->json($orders);
    }

    public function acceptOrder(Request $request, Order $order)
    {
        $rider = $request->user();

        if ($order->rider_id) {
            return response()->json(['message' => 'Order already assigned'], 400);
        }

        if ($order->status !== 'ready') {
            return response()->json(['message' => 'Order not ready for delivery'], 400);
        }

        $order->rider_id = $rider->id;
        $order->status = 'accepted';
        $order->save();

        return response()->json($order->load(['merchant', 'customer']));
    }

    public function updateLocation(Request $request)
    {
        $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
        ]);

        $user = $request->user();
        $rider = $user->rider;

        $rider->update([
            'current_lat' => $request->lat,
            'current_lng' => $request->lng,
        ]);

        // Find active orders for this rider and broadcast the location update
        $activeOrders = Order::where('rider_id', $user->id)
            ->whereIn('status', ['accepted', 'preparing', 'dispatched'])
            ->get();

        foreach ($activeOrders as $order) {
            event(new RiderLocationUpdated($order, $request->lat, $request->lng));
        }

        return response()->json(['message' => 'Location updated successfully']);
    }
}
