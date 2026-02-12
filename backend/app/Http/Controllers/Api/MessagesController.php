<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MessagesController extends Controller
{
    /**
     * Get all conversations for the authenticated user
     */
    public function conversations()
    {
        $user = auth()->user();

        $conversations = Conversation::with(['farmer', 'buyer', 'deal'])
            ->where(function ($q) use ($user) {
                $q->where('farmer_id', $user->id)
                  ->orWhere('buyer_id', $user->id);
            })
            ->orderBy('last_message_at', 'desc')
            ->get()
            ->map(function ($conversation) use ($user) {
                $otherUser = $conversation->farmer_id === $user->id 
                    ? $conversation->buyer 
                    : $conversation->farmer;
                
                return [
                    'id' => $conversation->id,
                    'other_user' => $otherUser,
                    'deal_id' => $conversation->deal_id,
                    'last_message_at' => $conversation->last_message_at,
                    'unread_count' => $conversation->unreadCountFor($user),
                ];
            });

        return response()->json($conversations);
    }

    /**
     * Get messages for a specific conversation
     */
    public function getMessages($conversationId)
    {
        $user = auth()->user();
        $conversation = Conversation::findOrFail($conversationId);

        // Verify user is part of the conversation
        if ($conversation->farmer_id !== $user->id && $conversation->buyer_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $messages = Message::where('conversation_id', $conversationId)
            ->with(['sender', 'receiver'])
            ->orderBy('created_at', 'asc')
            ->get();

        // Mark messages as read
        Message::where('conversation_id', $conversationId)
            ->where('receiver_id', $user->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return response()->json($messages);
    }

    /**
     * Send a message
     */
    public function sendMessage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'receiver_id' => 'required|exists:users,id',
            'deal_id' => 'nullable|exists:deals,id',
            'message' => 'required|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = auth()->user();
        $receiver = User::findOrFail($request->receiver_id);

        // Prevent sending messages to self
        if ($user->id === $receiver->id) {
            return response()->json(['error' => 'Cannot send message to yourself'], 422);
        }

        // Determine farmer and buyer IDs based on capabilities (with role fallback)
        $userIsSeller = $user->canSell() || $user->role === 'farmer';
        $userIsBuyer = $user->canBuy() || $user->role === 'buyer';
        $receiverIsSeller = $receiver->canSell() || $receiver->role === 'farmer';
        $receiverIsBuyer = $receiver->canBuy() || $receiver->role === 'buyer';
        
        // Assign farmer_id and buyer_id based on capabilities
        $farmerId = $userIsSeller ? $user->id : $receiver->id;
        $buyerId = $userIsBuyer ? $user->id : $receiver->id;

        DB::beginTransaction();
        try {
            // Find or create conversation
            $conversation = Conversation::firstOrCreate(
                [
                    'farmer_id' => $farmerId,
                    'buyer_id' => $buyerId,
                    'deal_id' => $request->deal_id,
                ],
                [
                    'last_message_at' => now(),
                ]
            );

            // Create message
            $message = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $user->id,
                'receiver_id' => $receiver->id,
                'message' => $request->message,
            ]);

            // Update conversation timestamp
            $conversation->update(['last_message_at' => now()]);

            DB::commit();

            return response()->json([
                'message' => 'Message sent successfully',
                'data' => $message->load(['sender', 'receiver']),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to send message: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Mark messages as read
     */
    public function markAsRead(Request $request, $conversationId)
    {
        $user = auth()->user();
        $conversation = Conversation::findOrFail($conversationId);

        // Verify user is part of the conversation
        if ($conversation->farmer_id !== $user->id && $conversation->buyer_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        Message::where('conversation_id', $conversationId)
            ->where('receiver_id', $user->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return response()->json(['message' => 'Messages marked as read']);
    }

    /**
     * Get unread message count
     */
    public function unreadCount()
    {
        $user = auth()->user();

        $count = Message::where('receiver_id', $user->id)
            ->where('is_read', false)
            ->count();

        return response()->json(['unread_count' => $count]);
    }
}
