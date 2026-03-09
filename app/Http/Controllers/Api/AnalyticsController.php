<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    /**
     * Admin Analytics: Comprehensive platform overview.
     */
    public function adminOverview(Request $request)
    {
        $days = $request->query('days', 30);
        $startDate = now()->subDays($days);

        $revenueTrend = Order::where('status', 'delivered')
            ->where('created_at', '>=', $startDate)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(total_amount) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $commissionTrend = Order::where('status', 'delivered')
            ->where('created_at', '>=', $startDate)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(commission_amount) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $topMerchants = Order::where('status', 'delivered')
            ->select('merchant_id', DB::raw('SUM(total_amount) as total_revenue'), DB::raw('count(*) as order_count'))
            ->with('merchant')
            ->groupBy('merchant_id')
            ->orderByDesc('total_revenue')
            ->limit(5)
            ->get();

        $orderStatusDistribution = Order::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get();

        return response()->json([
            'revenue_trend' => $revenueTrend,
            'commission_trend' => $commissionTrend,
            'top_merchants' => $topMerchants,
            'order_status_distribution' => $orderStatusDistribution,
        ]);
    }

    /**
     * Merchant Analytics: Sales trends, peak hours, customer behavior.
     */
    public function merchantOverview(Request $request)
    {
        $merchant = $request->user()->merchant;
        if (!$merchant) return response()->json(['message' => 'Unauthorized'], 403);

        $days = $request->query('days', 30);
        $startDate = now()->subDays($days);

        // Sales Trend
        $salesTrend = Order::where('merchant_id', $merchant->id)
            ->where('status', 'delivered')
            ->where('created_at', '>=', $startDate)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(total_amount) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Peak Hours
        $peakHours = Order::where('merchant_id', $merchant->id)
            ->where('created_at', '>=', $startDate)
            ->select(DB::raw('HOUR(created_at) as hour'), DB::raw('count(*) as count'))
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        // Top Products
        $topProducts = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('orders.merchant_id', $merchant->id)
            ->where('orders.status', 'delivered')
            ->select('products.name', DB::raw('SUM(order_items.quantity) as total_sold'))
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_sold')
            ->limit(5)
            ->get();

        return response()->json([
            'sales_trend' => $salesTrend,
            'peak_hours' => $peakHours,
            'top_products' => $topProducts,
        ]);
    }
}
