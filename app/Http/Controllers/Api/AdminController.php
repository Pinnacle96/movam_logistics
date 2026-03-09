<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\Merchant;
use App\Models\Rider;
use App\Models\Setting;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    public function dashboard(Request $request)
    {
        $stats = [
            'total_users' => User::count(),
            'total_merchants' => Merchant::count(),
            'total_riders' => Rider::count(),
            'total_orders' => Order::count(),
            'total_revenue' => Order::where('status', 'delivered')->sum('total_amount'),
            'platform_commission' => Order::where('status', 'delivered')->sum('commission_amount'),
            'recent_users' => User::with('roles')->latest()->limit(5)->get(),
        ];

        $order_stats = Order::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get();

        return response()->json([
            'stats' => $stats,
            'order_stats' => $order_stats,
        ]);
    }

    public function users(Request $request)
    {
        $role = $request->query('role');
        $search = $request->query('search');
        $query = User::with(['roles', 'merchant', 'rider', 'customer']);

        if ($role) {
            $query->role($role);
        }

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return response()->json($query->latest()->paginate(20));
    }

    public function storeUser(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|string|in:admin,merchant,rider,customer',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $user->assignRole($request->role);

        // Create empty profile based on role
        if ($request->role === 'merchant') {
            Merchant::create(['user_id' => $user->id, 'business_name' => $user->name, 'address' => '', 'phone' => '']);
        } elseif ($request->role === 'rider') {
            Rider::create(['user_id' => $user->id, 'vehicle_type' => '', 'vehicle_number' => '', 'license_number' => '']);
        }

        return response()->json($user->load('roles'), 201);
    }

    public function updateUser(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:8',
            'role' => 'required|string|in:admin,merchant,rider,customer',
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        if ($request->password) {
            $user->update(['password' => Hash::make($request->password)]);
        }

        $user->syncRoles([$request->role]);

        return response()->json($user->load('roles'));
    }

    public function deleteUser(User $user)
    {
        AuditLog::log('delete_user', $user, $user->toArray());
        $user->delete();
        return response()->json(['message' => 'User deleted successfully']);
    }

    public function merchants(Request $request)
    {
        $search = $request->query('search');
        $query = Merchant::with('user');

        if ($search) {
            $query->where('business_name', 'like', "%{$search}%");
        }

        return response()->json($query->latest()->paginate(20));
    }

    public function updateMerchantStatus(Request $request, Merchant $merchant)
    {
        $request->validate([
            'is_verified' => 'boolean',
            'is_active' => 'boolean',
            'commission_rate' => 'numeric|min:0|max:100',
        ]);

        $merchant->update($request->only(['is_verified', 'is_active', 'commission_rate']));

        return response()->json($merchant);
    }

    public function riders(Request $request)
    {
        $search = $request->query('search');
        $query = Rider::with('user');

        if ($search) {
            $query->whereHas('user', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        return response()->json($query->latest()->paginate(20));
    }

    public function updateRiderStatus(Request $request, Rider $rider)
    {
        $request->validate([
            'is_verified' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $rider->update($request->only(['is_verified', 'is_active']));

        return response()->json($rider);
    }

    public function orders(Request $request)
    {
        $status = $request->query('status');
        $search = $request->query('search');
        $query = Order::with(['customer', 'merchant', 'rider']);

        if ($status) {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function($sq) use ($search) {
                      $sq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        return response()->json($query->latest()->paginate(20));
    }

    public function settings()
    {
        $keys = [
            'platform_name' => config('app.name'),
            'delivery_fee_base' => 1000,
            'delivery_fee_per_km' => 200,
            'commission_rate_default' => 15,
            'min_withdrawal_amount' => 5000,
            'support_email' => 'support@movam.com',
            'maintenance_mode' => false,
        ];

        $settings = [];
        foreach ($keys as $key => $default) {
            $setting = Setting::where('key', $key)->first();
            $value = $setting ? $setting->value : $default;
            
            // Cast type
            if ($setting) {
                if ($setting->type === 'number') $value = (float)$value;
                if ($setting->type === 'boolean') $value = (bool)$value;
            }

            $settings[$key] = $value;
        }

        return response()->json($settings);
    }

    public function updateSettings(Request $request)
    {
        $oldSettings = Setting::all()->pluck('value', 'key')->toArray();
        
        foreach ($request->all() as $key => $value) {
            $type = is_bool($value) ? 'boolean' : (is_numeric($value) ? 'number' : 'string');
            
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value, 'type' => $type]
            );
        }

        AuditLog::log('update_settings', null, $oldSettings, $request->all());

        return response()->json(['message' => 'Settings updated successfully']);
    }
}
