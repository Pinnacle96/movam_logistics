<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use App\Models\MerchantSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubscriptionController extends Controller
{
    /**
     * List available plans
     */
    public function plans()
    {
        return response()->json(SubscriptionPlan::where('is_active', true)->get());
    }

    /**
     * Subscribe a merchant to a plan
     */
    public function subscribe(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id',
            'payment_reference' => 'required|string',
        ]);

        $merchant = $request->user()->merchant;
        if (!$merchant) return response()->json(['message' => 'Unauthorized'], 403);

        $plan = SubscriptionPlan::findOrFail($request->plan_id);

        // In production, we would verify payment with Paystack here
        // For now, we assume payment is successful

        return DB::transaction(function () use ($merchant, $plan, $request) {
            // Cancel existing active subscriptions
            MerchantSubscription::where('merchant_id', $merchant->id)
                ->where('status', 'active')
                ->update(['status' => 'cancelled']);

            $subscription = MerchantSubscription::create([
                'merchant_id' => $merchant->id,
                'subscription_plan_id' => $plan->id,
                'starts_at' => now(),
                'expires_at' => now()->addDays($plan->duration_days),
                'status' => 'active',
                'paid_amount' => $plan->price,
                'payment_reference' => $request->payment_reference,
            ]);

            return response()->json($subscription, 201);
        });
    }

    /**
     * Get merchant's current subscription
     */
    public function current(Request $request)
    {
        $merchant = $request->user()->merchant;
        if (!$merchant) return response()->json(['message' => 'Unauthorized'], 403);

        $subscription = MerchantSubscription::where('merchant_id', $merchant->id)
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->with('plan')
            ->first();

        return response()->json($subscription);
    }

    /**
     * Admin: Create a new plan
     */
    public function storePlan(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'price' => 'required|numeric|min:0',
            'duration_days' => 'required|integer|min:1',
            'features' => 'nullable|array',
        ]);

        $plan = SubscriptionPlan::create($request->all());
        return response()->json($plan, 201);
    }
}
