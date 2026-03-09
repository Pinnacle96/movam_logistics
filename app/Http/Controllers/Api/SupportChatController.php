<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\User;
use App\Events\MessageSent;
use Illuminate\Http\Request;

class SupportChatController extends Controller
{
    /**
     * Get chat messages between current user and support (or another user in order context)
     */
    public function index(Request $request)
    {
        $request->validate([
            'order_id' => 'nullable|exists:orders,id',
            'receiver_id' => 'required|exists:users,id',
        ]);

        $userId = $request->user()->id;
        $receiverId = $request->receiver_id;
        $orderId = $request->order_id;

        $query = ChatMessage::where(function($q) use ($userId, $receiverId) {
            $q->where('sender_id', $userId)->where('receiver_id', $receiverId);
        })->orWhere(function($q) use ($userId, $receiverId) {
            $q->where('sender_id', $receiverId)->where('receiver_id', $userId);
        });

        if ($orderId) {
            $query->where('order_id', $orderId);
        }

        return response()->json($query->oldest()->get());
    }

    /**
     * Send a new chat message
     */
    public function store(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'message' => 'required|string',
            'order_id' => 'nullable|exists:orders,id',
            'type' => 'nullable|string|in:text,image,file',
        ]);

        $message = ChatMessage::create([
            'sender_id' => $request->user()->id,
            'receiver_id' => $request->receiver_id,
            'message' => $request->message,
            'order_id' => $request->order_id,
            'type' => $request->type ?? 'text',
        ]);

        broadcast(new MessageSent($message))->toOthers();

        return response()->json($message, 201);
    }

    /**
     * Mark messages as read
     */
    public function markAsRead(Request $request)
    {
        $request->validate([
            'sender_id' => 'required|exists:users,id',
            'order_id' => 'nullable|exists:orders,id',
        ]);

        $query = ChatMessage::where('sender_id', $request->sender_id)
            ->where('receiver_id', $request->user()->id)
            ->whereNull('read_at');

        if ($request->order_id) {
            $query->where('order_id', $request->order_id);
        }

        $query->update(['read_at' => now()]);

        return response()->json(['message' => 'Messages marked as read']);
    }
}
