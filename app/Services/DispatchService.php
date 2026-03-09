<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Rider;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class DispatchService
{
    /**
     * Find the nearest available rider for an order.
     */
    public function findNearestRider(Order $order): ?Rider
    {
        $pickupLat = $order->pickup_lat;
        $pickupLng = $order->pickup_lng;

        // Get all available and verified riders
        $riders = Rider::where('is_available', true)
            ->where('is_verified', true)
            ->whereNotNull('current_lat')
            ->whereNotNull('current_lng')
            ->get();

        if ($riders->isEmpty()) {
            return null;
        }

        $nearestRider = null;
        $minDistance = PHP_INT_MAX;

        foreach ($riders as $rider) {
            $distance = $this->calculateDistance($pickupLat, $pickupLng, $rider->current_lat, $rider->current_lng);
            if ($distance < $minDistance) {
                $minDistance = $distance;
                $nearestRider = $rider;
            }
        }

        return $nearestRider;
    }

    /**
     * Calculate distance between two points using Haversine formula.
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
        return $miles * 1.609344; // to KM
    }

    /**
     * Algorithmic dispatch: Assign to rider and notify.
     */
    public function dispatchOrder(Order $order): bool
    {
        $rider = $this->findNearestRider($order);

        if ($rider) {
            $order->update([
                'rider_id' => $rider->user_id,
                'status' => 'accepted', // Auto-accepted by dispatch logic for efficiency
            ]);

            // Notify Rider
            $notificationService = app(NotificationService::class);
            $notificationService->sendPush($rider->user, "New Order Assigned", "You have a new delivery assignment #{$order->order_number}.", [
                'order_id' => $order->id,
                'action' => 'new_delivery'
            ]);

            return true;
        }

        return false;
    }
}
