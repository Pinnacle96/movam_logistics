<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class ReferralController extends Controller
{
    /**
     * Display the user's referral information.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Ensure referral code exists
        if (!$user->referral_code) {
            $user->referral_code = User::generateReferralCode();
            $user->save();
        }

        $referrals = $user->referrals()
            ->with(['referred:id,name,email,created_at'])
            ->latest()
            ->paginate(20);

        return response()->json([
            'referral_code' => $user->referral_code,
            'total_referrals' => $user->referrals()->count(),
            'total_earnings' => $user->referrals()->where('status', 'paid')->sum('reward_amount'),
            'referrals' => $referrals,
        ]);
    }
}
