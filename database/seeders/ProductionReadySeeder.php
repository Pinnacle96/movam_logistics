<?php

namespace Database\Seeders;

use App\Models\Coupon;
use App\Models\SubscriptionPlan;
use App\Models\AuditLog;
use App\Models\Dispute;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProductionReadySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Subscription Plans
        SubscriptionPlan::create([
            'name' => 'Basic Merchant',
            'description' => 'Standard listing for small vendors.',
            'price' => 5000,
            'duration_days' => 30,
            'features' => ['standard_listing'],
        ]);

        SubscriptionPlan::create([
            'name' => 'Premium Merchant',
            'description' => 'Priority listing and lower commission rates.',
            'price' => 15000,
            'duration_days' => 30,
            'features' => ['priority_listing', 'lower_commission'],
        ]);

        // 2. Coupons
        Coupon::create([
            'code' => 'WELCOME10',
            'type' => 'percentage',
            'value' => 10,
            'min_order_amount' => 1000,
            'starts_at' => now(),
            'expires_at' => now()->addMonths(3),
            'usage_limit' => 100,
            'is_active' => true,
        ]);

        Coupon::create([
            'code' => 'FREESHIP',
            'type' => 'fixed',
            'value' => 1000,
            'min_order_amount' => 5000,
            'starts_at' => now(),
            'expires_at' => now()->addMonth(),
            'is_active' => true,
        ]);

        // 3. Audit Logs (Dummy)
        $admin = User::role('admin')->first();
        if ($admin) {
            AuditLog::log('update_settings', null, ['delivery_fee_base' => 1000], ['delivery_fee_base' => 1200]);
            AuditLog::log('resolve_dispute', null, null, ['dispute_id' => 1, 'resolution' => 'refund_full']);
        }

        // 4. Disputes (Dummy)
        $order = Order::first();
        $customer = User::role('customer')->first();
        if ($order && $customer) {
            Dispute::create([
                'order_id' => $order->id,
                'user_id' => $customer->id,
                'reason' => 'Damaged items',
                'description' => 'The food was spilled upon arrival.',
                'status' => 'pending',
            ]);
        }
    }
}
