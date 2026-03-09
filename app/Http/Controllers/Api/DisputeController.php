<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Dispute;
use App\Models\Order;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DisputeController extends Controller
{
    protected $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    /**
     * List disputes for admin
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Dispute::with(['order', 'user']);

        if ($user->hasRole('customer')) {
            $query->where('user_id', $user->id);
        } elseif ($user->hasRole('merchant')) {
            $query->whereHas('order', function($q) use ($user) {
                $q->where('merchant_id', $user->merchant->id);
            });
        }

        return response()->json($query->latest()->paginate(20));
    }

    /**
     * Create a new dispute
     */
    public function store(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'reason' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $order = Order::findOrFail($request->order_id);

        // Check if user is part of the order
        if ($request->user()->id !== $order->customer_id && (!$request->user()->merchant || $request->user()->merchant->id !== $order->merchant_id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $dispute = Dispute::create([
            'order_id' => $order->id,
            'user_id' => $request->user()->id,
            'reason' => $request->reason,
            'description' => $request->description,
            'status' => 'pending',
        ]);

        return response()->json($dispute, 201);
    }

    /**
     * Resolve a dispute (Admin only)
     */
    public function resolve(Request $request, Dispute $dispute)
    {
        $request->validate([
            'resolution' => 'required|in:refund_full,refund_partial,rejected,other',
            'refund_amount' => 'required_if:resolution,refund_partial|numeric|min:0',
            'admin_notes' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($request, $dispute) {
            $refundAmount = 0;
            if ($request->resolution === 'refund_full') {
                $refundAmount = $dispute->order->total_amount;
            } elseif ($request->resolution === 'refund_partial') {
                $refundAmount = $request->refund_amount;
            }

            if ($refundAmount > 0) {
                // Process refund from Admin/Clearing wallet back to customer
                $adminUser = \App\Models\User::role('admin')->first();
                $customerUser = $dispute->order->customer;
                
                $this->walletService->withdraw($adminUser, $refundAmount, "Refund for order #{$dispute->order->order_number} (Dispute #{$dispute->id})");
                $this->walletService->deposit($customerUser, $refundAmount, "Refund for order #{$dispute->order->order_number} (Dispute #{$dispute->id})");
            }

            $dispute->update([
                'status' => 'resolved',
                'resolution' => $request->resolution,
                'refund_amount' => $refundAmount,
                'admin_notes' => $request->admin_notes,
            ]);

            return response()->json($dispute);
        });
    }
}
