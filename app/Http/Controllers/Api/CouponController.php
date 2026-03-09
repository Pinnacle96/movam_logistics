<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CouponController extends Controller
{
    /**
     * List coupons for admin or merchant
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Coupon::query();

        if ($user->hasRole('merchant')) {
            $query->where('merchant_id', $user->merchant->id);
        }

        return response()->json($query->latest()->paginate(20));
    }

    /**
     * Create a new coupon
     */
    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|unique:coupons,code',
            'type' => 'required|in:fixed,percentage',
            'value' => 'required|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after:starts_at',
            'usage_limit' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
        ]);

        $data = $request->all();
        if ($request->user()->hasRole('merchant')) {
            $data['merchant_id'] = $request->user()->merchant->id;
        }

        $coupon = Coupon::create($data);

        return response()->json($coupon, 201);
    }

    /**
     * Validate a coupon for a customer
     */
    public function validateCoupon(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'order_amount' => 'required|numeric|min:0',
        ]);

        $coupon = Coupon::where('code', $request->code)->first();

        if (!$coupon) {
            return response()->json(['message' => 'Invalid coupon code'], 404);
        }

        if (!$coupon->isValidForUser($request->user(), $request->order_amount)) {
            return response()->json(['message' => 'Coupon is not applicable to your order'], 400);
        }

        $discount = $coupon->calculateDiscount($request->order_amount);

        return response()->json([
            'valid' => true,
            'coupon' => $coupon,
            'discount_amount' => $discount,
            'new_total' => $request->order_amount - $discount
        ]);
    }

    /**
     * Update a coupon
     */
    public function update(Request $request, Coupon $coupon)
    {
        // Check authorization if merchant
        if ($request->user()->hasRole('merchant') && $coupon->merchant_id !== $request->user()->merchant->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'type' => 'required|in:fixed,percentage',
            'value' => 'required|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after:starts_at',
            'usage_limit' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
        ]);

        $coupon->update($request->all());

        return response()->json($coupon);
    }

    /**
     * Delete a coupon
     */
    public function destroy(Request $request, Coupon $coupon)
    {
        if ($request->user()->hasRole('merchant') && $coupon->merchant_id !== $request->user()->merchant->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $coupon->delete();
        return response()->json(['message' => 'Coupon deleted successfully']);
    }
}
