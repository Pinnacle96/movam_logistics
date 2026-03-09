<?php

namespace App\Services;

use App\Models\User;
use App\Models\Wallet;
use App\Models\Transaction;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WalletService
{
    public function createWallet(User $user, string $currency = 'NGN'): Wallet
    {
        return Wallet::create([
            'user_id' => $user->id,
            'balance' => 0.00,
            'currency' => $currency,
        ]);
    }

    public function deposit(User $user, float $amount, string $description = 'Deposit', array $metadata = []): Transaction
    {
        return DB::transaction(function () use ($user, $amount, $description, $metadata) {
            $wallet = $user->wallet ?: $this->createWallet($user);
            $wallet->increment('balance', $amount);

            return Transaction::create([
                'wallet_id' => $wallet->id,
                'amount' => $amount,
                'type' => 'credit',
                'description' => $description,
                'reference' => 'DEP-' . strtoupper(Str::random(10)),
                'status' => 'completed',
                'metadata' => $metadata,
            ]);
        });
    }

    public function withdraw(User $user, float $amount, string $description = 'Withdrawal', array $metadata = []): Transaction
    {
        return DB::transaction(function () use ($user, $amount, $description, $metadata) {
            $wallet = $user->wallet ?: $this->createWallet($user);

            if ($wallet->balance < $amount) {
                throw new \Exception('Insufficient wallet balance');
            }

            $wallet->decrement('balance', $amount);

            return Transaction::create([
                'wallet_id' => $wallet->id,
                'amount' => -$amount,
                'type' => 'debit',
                'description' => $description,
                'reference' => 'WTH-' . strtoupper(Str::random(10)),
                'status' => 'completed',
                'metadata' => $metadata,
            ]);
        });
    }

    public function distributeOrderFunds(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $adminUser = User::role('admin')->first();
            
            // 1. Merchant Share
            $merchantUser = $order->merchant->user;
            $merchantAmount = $order->total_amount - $order->commission_amount - $order->delivery_fee;
            
            // Deduct from Admin (Clearing)
            $this->withdraw($adminUser, $merchantAmount, "Merchant Payout for order #{$order->order_number}");
            // Deposit to Merchant
            $this->deposit($merchantUser, $merchantAmount, "Payment for order #{$order->order_number}", [
                'order_id' => $order->id,
                'type' => 'order_payment'
            ]);

            // 2. Rider Share
            if ($order->rider_id) {
                $riderUser = $order->rider;
                // Deduct from Admin
                $this->withdraw($adminUser, $order->rider_share, "Rider Payout for order #{$order->order_number}");
                // Deposit to Rider
                $this->deposit($riderUser, $order->rider_share, "Delivery fee for order #{$order->order_number}", [
                    'order_id' => $order->id,
                    'type' => 'delivery_fee'
                ]);
            }

            // Note: Admin keeps the Commission + Platform fee (already in Admin wallet from initial payment)
            
            $order->update(['payment_status' => 'paid']);
        });
    }
}
