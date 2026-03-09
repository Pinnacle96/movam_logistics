<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaystackService
{
    protected $secretKey;
    protected $baseUrl;

    public function __construct()
    {
        $this->secretKey = env('PAYSTACK_SECRET_KEY');
        $this->baseUrl = env('PAYSTACK_PAYMENT_URL', 'https://api.paystack.co');
    }

    /**
     * Initialize a transaction
     */
    public function initializeTransaction(array $data)
    {
        $response = Http::withToken($this->secretKey)
            ->post("{$this->baseUrl}/transaction/initialize", $data);

        if ($response->successful()) {
            return $response->json()['data'];
        }

        Log::error('Paystack Initialization Failed', [
            'response' => $response->body(),
            'data' => $data
        ]);

        return null;
    }

    /**
     * Verify a transaction
     */
    public function verifyTransaction($reference)
    {
        $response = Http::withToken($this->secretKey)
            ->get("{$this->baseUrl}/transaction/verify/{$reference}");

        if ($response->successful()) {
            return $response->json()['data'];
        }

        Log::error('Paystack Verification Failed', [
            'reference' => $reference,
            'response' => $response->body()
        ]);

        return null;
    }

    /**
     * Create a transfer recipient
     */
    public function createTransferRecipient(array $data)
    {
        $response = Http::withToken($this->secretKey)
            ->post("{$this->baseUrl}/transferrecipient", [
                'type' => 'nuban',
                'name' => $data['name'],
                'account_number' => $data['account_number'],
                'bank_code' => $data['bank_code'],
                'currency' => 'NGN'
            ]);

        if ($response->successful()) {
            return $response->json()['data'];
        }

        Log::error('Paystack Transfer Recipient Creation Failed', [
            'response' => $response->body(),
            'data' => $data
        ]);

        return null;
    }

    /**
     * Initiate a transfer
     */
    public function initiateTransfer(array $data)
    {
        $response = Http::withToken($this->secretKey)
            ->post("{$this->baseUrl}/transfer", [
                'source' => 'balance',
                'amount' => $data['amount'],
                'recipient' => $data['recipient_code'],
                'reason' => $data['reason'] ?? 'Withdrawal from Movam'
            ]);

        if ($response->successful()) {
            return $response->json()['data'];
        }

        Log::error('Paystack Transfer Initiation Failed', [
            'response' => $response->body(),
            'data' => $data
        ]);

        return null;
    }

    /**
     * List Banks
     */
    public function getBanks()
    {
        $response = Http::withToken($this->secretKey)
            ->get("{$this->baseUrl}/bank");

        if ($response->successful()) {
            return $response->json()['data'];
        }

        return [];
    }
}
