<?php

namespace App\Services;

use App\Models\Otp;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class OtpService
{
    /**
     * Generate a new OTP
     */
    public function generate($identifier, $type, $expiresInMinutes = 15)
    {
        // Deactivate existing OTPs of the same type for this identifier
        Otp::where('identifier', $identifier)
            ->where('type', $type)
            ->where('is_used', false)
            ->update(['is_used' => true]);

        $token = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

        return Otp::create([
            'identifier' => $identifier,
            'token' => $token,
            'type' => $type,
            'expires_at' => Carbon::now()->addMinutes($expiresInMinutes),
        ]);
    }

    /**
     * Verify an OTP
     */
    public function verify($identifier, $token, $type)
    {
        $otp = Otp::where('identifier', $identifier)
            ->where('token', $token)
            ->where('type', $type)
            ->where('is_used', false)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if ($otp) {
            $otp->update(['is_used' => true]);
            return true;
        }

        return false;
    }
}
