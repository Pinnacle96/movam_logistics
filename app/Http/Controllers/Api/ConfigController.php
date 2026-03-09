<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Setting;

class ConfigController extends Controller
{
    /**
     * Get mobile app configuration.
     * Use this endpoint on app launch to check for updates and maintenance.
     */
    public function index()
    {
        return response()->json([
            'android' => [
                'min_version' => Setting::get('android_min_version', '1.0.0'),
                'latest_version' => Setting::get('android_latest_version', '1.0.0'),
                'force_update' => (bool) Setting::get('android_force_update', false),
                'store_url' => Setting::get('android_store_url', 'https://play.google.com/store/apps/details?id=com.movam.app'),
            ],
            'ios' => [
                'min_version' => Setting::get('ios_min_version', '1.0.0'),
                'latest_version' => Setting::get('ios_latest_version', '1.0.0'),
                'force_update' => (bool) Setting::get('ios_force_update', false),
                'store_url' => Setting::get('ios_store_url', 'https://apps.apple.com/app/id123456789'),
            ],
            'maintenance_mode' => (bool) Setting::get('maintenance_mode', false),
            'support' => [
                'phone' => Setting::get('support_phone', '+2348000000000'),
                'email' => Setting::get('support_email', 'support@movam.com'),
                'whatsapp' => Setting::get('support_whatsapp', 'https://wa.me/2348000000000'),
            ],
            'features' => [
                'referrals_enabled' => (bool) Setting::get('feature_referrals', true),
                'wallet_enabled' => (bool) Setting::get('feature_wallet', true),
            ],
        ]);
    }
}
