<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\Review;
use App\Models\Rider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    /**
     * Store a newly created review in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'type' => 'required|in:merchant,rider',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:500',
        ]);

        $user = $request->user();
        $order = Order::findOrFail($request->order_id);

        // 1. Authorization: Only the customer who placed the order can review
        if ($order->customer_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized. You did not place this order.'], 403);
        }

        // 2. State Check: Order must be delivered
        if ($order->status !== 'delivered') {
            return response()->json(['message' => 'You can only review delivered orders.'], 400);
        }

        // 3. Determine Review Target
        $reviewableType = null;
        $reviewableId = null;

        if ($request->type === 'merchant') {
            $reviewableType = Merchant::class;
            $reviewableId = $order->merchant_id;
        } else {
            if (!$order->rider_id) {
                return response()->json(['message' => 'No rider was assigned to this order.'], 400);
            }
            $reviewableType = Rider::class;
            $reviewableId = $order->rider_id; // Currently rider_id is User ID, need to get Rider ID?
            // Wait, in Order model: rider_id refers to User ID (rider role).
            // But Review morphs to Rider model.
            // Let's check Order model definition.
            // Order defines rider() as BelongsTo User.
            // Rider model has user_id.
            // So if Order->rider_id is a User ID, we need to find the Rider profile associated with that User.
            
            $rider = Rider::where('user_id', $order->rider_id)->first();
            if (!$rider) {
                 return response()->json(['message' => 'Rider profile not found.'], 404);
            }
            $reviewableId = $rider->id;
        }

        // 4. Duplicate Check
        $exists = Review::where('order_id', $order->id)
            ->where('reviewable_type', $reviewableType)
            ->where('reviewable_id', $reviewableId)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'You have already reviewed this ' . $request->type . ' for this order.'], 409);
        }

        // 5. Create Review
        $review = Review::create([
            'customer_id' => $user->id,
            'order_id' => $order->id,
            'reviewable_type' => $reviewableType,
            'reviewable_id' => $reviewableId,
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        return response()->json($review, 201);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, $type, $id)
    {
        $reviewableType = null;

        if ($type === 'merchant') {
            $reviewableType = Merchant::class;
        } elseif ($type === 'rider') {
            $reviewableType = Rider::class;
        } else {
            return response()->json(['message' => 'Invalid type. Must be merchant or rider.'], 400);
        }

        $reviews = Review::where('reviewable_type', $reviewableType)
            ->where('reviewable_id', $id)
            ->with('customer:id,name,email') // Only safe fields
            ->latest()
            ->paginate(15);

        return response()->json($reviews);
    }
}
