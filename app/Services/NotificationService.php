<?php

namespace App\Services;

use App\Models\User;
use App\Models\Order;
use App\Mail\OtpMail;
use App\Mail\OrderReceiptMail;
use App\Mail\WithdrawalOtpMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Barryvdh\DomPDF\Facade\Pdf;

class NotificationService
{
    public function sendRegistrationOtp(string $email, string $token)
    {
        try {
            Mail::to($email)->send(new OtpMail($token));
        } catch (\Exception $e) {
            Log::error("Failed to send Registration OTP: " . $e->getMessage());
        }
    }

    public function sendWithdrawalOtp(User $user, string $token, float $amount)
    {
        try {
            Mail::to($user->email)->send(new WithdrawalOtpMail($token, $amount));
        } catch (\Exception $e) {
            Log::error("Failed to send Withdrawal OTP: " . $e->getMessage());
        }
    }

    public function sendOrderReceipt(Order $order)
    {
        try {
            $pdf = Pdf::loadView('emails.receipt', ['order' => $order]);
            $pdfContent = $pdf->output();

            Mail::to($order->customer->email)->send(new OrderReceiptMail($order, $pdfContent));
        } catch (\Exception $e) {
            Log::error("Failed to send Order Receipt: " . $e->getMessage());
        }
    }

    public function sendPush(User $user, string $title, string $body, array $data = [])
    {
        // For production grade, we dispatch to queue
        // In local development, if queue is sync, it will run immediately
        Log::info("Dispatching Push Notification to User {$user->id}: {$title} - {$body}", $data);
        
        // This avoids infinite recursion if the job calls sendPush
        if (app()->runningInConsole() && str_contains(debug_backtrace()[1]['class'] ?? '', 'SendNotification')) {
             // Actual logic to call FCM would go here
             Log::info("ACTUAL FCM CALL (Placeholder)");
             return;
        }

        \App\Jobs\SendNotification::dispatch($user, $title, $body, $data);
    }

    public function notifyOrderStatus(User $user, string $orderNumber, string $status)
    {
        $title = "Order Status Update";
        $body = "Your order #{$orderNumber} is now {$status}.";
        
        $this->sendPush($user, $title, $body, ['order_number' => $orderNumber, 'status' => $status]);
    }

    public function notifyLowStock(\App\Models\Product $product)
    {
        $merchantUser = $product->merchant->user;
        $title = "Low Stock Alert";
        $body = "Product '{$product->name}' is running low on stock ({$product->stock} remaining).";
        
        $this->sendPush($merchantUser, $title, $body, ['product_id' => $product->id, 'stock' => $product->stock]);
    }
}
