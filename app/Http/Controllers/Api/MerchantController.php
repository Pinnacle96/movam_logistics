<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MerchantController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');
        $query = Merchant::where('is_active', true)->where('is_verified', true);

        if ($search) {
            $query->where('business_name', 'like', "%{$search}%");
        }

        $merchants = $query->latest()->get();
        
        // Sort by priority listing
        return response()->json($merchants->sortByDesc(function($merchant) {
            return $merchant->hasPriorityListing();
        })->values());
    }

    public function show($id)
    {
        $merchant = Merchant::where(function($query) use ($id) {
            $query->where('id', $id)->orWhere('slug', $id);
        })->where('is_active', true)->where('is_verified', true)->firstOrFail();
        return response()->json($merchant);
    }

    public function dashboard(Request $request)
    {
        $merchant = $request->user()->merchant;

        if (!$merchant) {
            return response()->json(['message' => 'Merchant profile not found'], 404);
        }

        $stats = [
            'total_orders' => Order::where('merchant_id', $merchant->id)->count(),
            'pending_orders' => Order::where('merchant_id', $merchant->id)->where('status', 'pending')->count(),
            'total_revenue' => Order::where('merchant_id', $merchant->id)->where('status', 'delivered')->sum('total_amount'),
            'total_products' => Product::where('merchant_id', $merchant->id)->count(),
        ];

        $recent_orders = Order::where('merchant_id', $merchant->id)
            ->with(['customer', 'items.product'])
            ->latest()
            ->limit(5)
            ->get();

        $revenue_data = Order::where('merchant_id', $merchant->id)
            ->where('status', 'delivered')
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(total_amount) as revenue'))
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->limit(7)
            ->get();

        return response()->json([
            'merchant' => $merchant,
            'stats' => $stats,
            'recent_orders' => $recent_orders,
            'revenue_data' => $revenue_data,
        ]);
    }

    public function orders(Request $request)
    {
        $merchant = $request->user()->merchant;
        $status = $request->query('status');

        $query = Order::where('merchant_id', $merchant->id)
            ->with(['customer', 'items.product', 'rider']);

        if ($status) {
            $query->where('status', $status);
        }

        return response()->json($query->latest()->paginate(15));
    }

    public function products(Request $request)
    {
        $merchant = $request->user()->merchant;
        return response()->json(Product::where('merchant_id', $merchant->id)->with('category')->latest()->get());
    }
}
