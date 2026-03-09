<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Services\OtpService;
use App\Services\PaystackService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Services\NotificationService;

class WalletController extends Controller
{
    protected $paystack;
    protected $otpService;
    protected $notificationService;

    public function __construct(PaystackService $paystack, OtpService $otpService, NotificationService $notificationService)
    {
        $this->paystack = $paystack;
        $this->otpService = $otpService;
        $this->notificationService = $notificationService;
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $wallet = $user->wallet ?: Wallet::firstOrCreate(['user_id' => $user->id], ['balance' => 0]);
        
        $transactions = Transaction::where('wallet_id', $wallet->id)
            ->latest()
            ->paginate(15);

        return response()->json([
            'wallet' => $wallet,
            'transactions' => $transactions
        ]);
    }

    /**
     * Customer: Add funds to wallet (Initialize Paystack)
     */
    public function addFunds(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:100',
        ]);

        $user = $request->user();
        
        $data = [
            'amount' => (int)($request->amount * 100),
            'email' => $user->email,
            'reference' => 'WAL-' . strtoupper(uniqid()),
            'metadata' => [
                'user_id' => $user->id,
                'type' => 'wallet_topup'
            ]
        ];

        $payment = $this->paystack->initializeTransaction($data);

        if (!$payment) {
            return response()->json(['message' => 'Failed to initialize payment'], 500);
        }

        return response()->json($payment);
    }

    /**
     * Customer: Verify wallet topup
     */
    public function verifyAddFunds(Request $request)
    {
        $request->validate(['reference' => 'required|string']);

        $paymentData = $this->paystack->verifyTransaction($request->reference);

        if (!$paymentData || $paymentData['status'] !== 'success') {
            return response()->json(['message' => 'Payment verification failed'], 400);
        }

        $userId = $paymentData['metadata']['user_id'];
        $user = \App\Models\User::find($userId);
        $wallet = $user->wallet;

        // Check if reference already processed
        if (Transaction::where('reference', $request->reference)->exists()) {
            return response()->json(['message' => 'Transaction already processed']);
        }

        return DB::transaction(function () use ($wallet, $paymentData, $request) {
            $amount = $paymentData['amount'] / 100;
            $wallet->increment('balance', $amount);

            Transaction::create([
                'wallet_id' => $wallet->id,
                'amount' => $amount,
                'type' => 'credit',
                'status' => 'completed',
                'description' => "Wallet Top-up",
                'reference' => $request->reference,
            ]);

            return response()->json(['message' => 'Wallet topped up successfully', 'balance' => $wallet->balance]);
        });
    }

    /**
     * Merchant/Rider/Admin: Request Withdrawal (Sends OTP)
     */
    public function requestWithdrawal(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1000',
            'bank_code' => 'required|string',
            'account_number' => 'required|string|size:10',
            'account_name' => 'required|string',
        ]);

        $user = $request->user();
        $wallet = $user->wallet;

        if ($wallet->balance < $request->amount) {
            return response()->json(['message' => 'Insufficient balance'], 400);
        }

        // Generate OTP
        $otp = $this->otpService->generate($user->email, 'withdrawal');

        // Send Email
        $this->notificationService->sendWithdrawalOtp($user, $otp->token, $request->amount);

        return response()->json(['message' => 'OTP sent to your email for withdrawal confirmation']);
    }

    /**
     * Merchant/Rider/Admin: Confirm Withdrawal (Verify OTP & Process via Paystack)
     */
    public function confirmWithdrawal(Request $request)
    {
        $request->validate([
            'otp' => 'required|string|size:6',
            'amount' => 'required|numeric|min:1000',
            'bank_code' => 'required|string',
            'account_number' => 'required|string|size:10',
            'account_name' => 'required|string',
        ]);

        $user = $request->user();
        $wallet = $user->wallet;

        if ($wallet->balance < $request->amount) {
            return response()->json(['message' => 'Insufficient balance'], 400);
        }

        // Verify OTP
        if (!$this->otpService->verify($user->email, $request->otp, 'withdrawal')) {
            return response()->json(['message' => 'Invalid or expired OTP'], 400);
        }

        // 1. Create Transfer Recipient
        $recipient = $this->paystack->createTransferRecipient([
            'name' => $request->account_name,
            'account_number' => $request->account_number,
            'bank_code' => $request->bank_code
        ]);

        if (!$recipient) {
            return response()->json(['message' => 'Failed to create transfer recipient'], 500);
        }

        // 2. Initiate Transfer
        $transfer = $this->paystack->initiateTransfer([
            'amount' => (int)($request->amount * 100),
            'recipient_code' => $recipient['recipient_code'],
            'reason' => "Withdrawal for {$user->name}"
        ]);

        if (!$transfer) {
            return response()->json(['message' => 'Transfer initiation failed'], 500);
        }

        return DB::transaction(function () use ($wallet, $request, $transfer) {
            $wallet->decrement('balance', $request->amount);

            Transaction::create([
                'wallet_id' => $wallet->id,
                'amount' => -$request->amount,
                'type' => 'withdrawal',
                'status' => 'pending',
                'description' => "Withdrawal to {$request->account_name}",
                'reference' => $transfer['transfer_code'],
            ]);

            return response()->json(['message' => 'Withdrawal initiated successfully. Funds will be processed shortly.']);
        });
    }

    public function getBanks()
    {
        return response()->json($this->paystack->getBanks());
    }
}
