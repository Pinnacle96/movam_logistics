<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Customer;
use App\Models\Merchant;
use App\Models\Rider;
use App\Models\Referral;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

use App\Services\NotificationService;
use App\Services\OtpService;
use Illuminate\Support\Facades\Cache;

class AuthController extends Controller
{
    protected $walletService;
    protected $otpService;
    protected $notificationService;

    public function __construct(WalletService $walletService, OtpService $otpService, NotificationService $notificationService)
    {
        $this->walletService = $walletService;
        $this->otpService = $otpService;
        $this->notificationService = $notificationService;
    }

    public function sendRegistrationOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email|max:255|unique:users',
        ]);

        $otp = $this->otpService->generate($request->email, 'registration');

        // Send Email
        $this->notificationService->sendRegistrationOtp($request->email, $otp->token);

        return response()->json(['message' => 'OTP sent to your email']);
    }

    public function registerCustomer(Request $request)
    {
        $request->validate([
            'otp' => 'required|string|size:6',
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'referral_code' => 'nullable|string|exists:users,referral_code',
        ]);

        if (!$this->otpService->verify($request->email, $request->otp, 'registration')) {
            return response()->json(['message' => 'Invalid or expired OTP'], 400);
        }

        return DB::transaction(function () use ($request) {
            $referredBy = null;
            if ($request->referral_code) {
                $referredBy = User::where('referral_code', $request->referral_code)->first()->id;
            }

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'referral_code' => User::generateReferralCode(),
                'referred_by' => $referredBy,
            ]);

            if ($referredBy) {
                Referral::create([
                    'referrer_id' => $referredBy,
                    'referred_id' => $user->id,
                    'status' => 'pending',
                ]);
            }

            if ($referredBy) {
                Referral::create([
                    'referrer_id' => $referredBy,
                    'referred_id' => $user->id,
                    'status' => 'pending',
                ]);
            }

            if ($referredBy) {
                Referral::create([
                    'referrer_id' => $referredBy,
                    'referred_id' => $user->id,
                    'status' => 'pending',
                ]);
            }

            $user->assignRole('customer');

            Customer::create([
                'user_id' => $user->id,
                'phone' => $request->phone,
                'address' => $request->address,
            ]);

            $this->walletService->createWallet($user);

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'user' => $user->load(['customer', 'wallet', 'roles']),
                'access_token' => $token,
                'token_type' => 'Bearer',
            ], 201);
        });
    }

    public function registerMerchant(Request $request)
    {
        $request->validate([
            'otp' => 'required|string|size:6',
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'business_name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'referral_code' => 'nullable|string|exists:users,referral_code',
        ]);

        if (!$this->otpService->verify($request->email, $request->otp, 'registration')) {
            return response()->json(['message' => 'Invalid or expired OTP'], 400);
        }

        return DB::transaction(function () use ($request) {
            $referredBy = null;
            if ($request->referral_code) {
                $referredBy = User::where('referral_code', $request->referral_code)->first()->id;
            }

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'referral_code' => User::generateReferralCode(),
                'referred_by' => $referredBy,
            ]);

            if ($referredBy) {
                Referral::create([
                    'referrer_id' => $referredBy,
                    'referred_id' => $user->id,
                    'status' => 'pending',
                ]);
            }

            $user->assignRole('merchant');

            Merchant::create([
                'user_id' => $user->id,
                'business_name' => $request->business_name,
                'address' => $request->address,
                'phone' => $request->phone,
            ]);

            $this->walletService->createWallet($user);

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'user' => $user->load(['merchant', 'wallet', 'roles']),
                'access_token' => $token,
                'token_type' => 'Bearer',
            ], 201);
        });
    }

    public function registerRider(Request $request)
    {
        $request->validate([
            'otp' => 'required|string|size:6',
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'vehicle_type' => 'required|string|max:50',
            'vehicle_number' => 'required|string|max:50',
            'license_number' => 'required|string|max:50',
            'referral_code' => 'nullable|string|exists:users,referral_code',
        ]);

        if (!$this->otpService->verify($request->email, $request->otp, 'registration')) {
            return response()->json(['message' => 'Invalid or expired OTP'], 400);
        }

        return DB::transaction(function () use ($request) {
            $referredBy = null;
            if ($request->referral_code) {
                $referredBy = User::where('referral_code', $request->referral_code)->first()->id;
            }

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'referral_code' => User::generateReferralCode(),
                'referred_by' => $referredBy,
            ]);

            if ($referredBy) {
                Referral::create([
                    'referrer_id' => $referredBy,
                    'referred_id' => $user->id,
                    'status' => 'pending',
                ]);
            }

            $user->assignRole('rider');

            Rider::create([
                'user_id' => $user->id,
                'vehicle_type' => $request->vehicle_type,
                'vehicle_number' => $request->vehicle_number,
                'license_number' => $request->license_number,
            ]);

            $this->walletService->createWallet($user);

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'user' => $user->load(['rider', 'wallet', 'roles']),
                'access_token' => $token,
                'token_type' => 'Bearer',
            ], 201);
        });
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user->load(['customer', 'merchant', 'rider', 'wallet', 'roles']),
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function user(Request $request)
    {
        return response()->json($request->user()->load(['customer', 'merchant', 'rider', 'wallet', 'roles']));
    }
}
