<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Deal;
use App\Models\FarmerListing;
use App\Models\BuyerRequest;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DealsController extends Controller
{
    /**
     * Get all deals for the authenticated user
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $status = $request->query('status');

        $query = Deal::with(['farmer', 'buyer', 'product', 'farmerListing', 'buyerRequest'])
            ->where(function ($q) use ($user) {
                $q->where('farmer_id', $user->id)
                  ->orWhere('buyer_id', $user->id);
            });

        if ($status) {
            $query->where('status', $status);
        }

        $deals = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json($deals);
    }

    /**
     * Get a single deal by ID
     */
    public function show($id)
    {
        $user = auth()->user();
        $deal = Deal::with(['farmer', 'buyer', 'product', 'farmerListing', 'buyerRequest', 'reviews'])
            ->findOrFail($id);

        // Verify user is part of the deal
        if ($deal->farmer_id !== $user->id && $deal->buyer_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json($deal);
    }

    /**
     * Buyer creates a deal from a farmer listing
     */
    public function createFromListing(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'farmer_listing_id' => 'required|exists:farmer_listings,id',
            'quantity' => 'required|numeric|min:0.01',
            'delivery_location' => 'required|string|max:255',
            'delivery_date' => 'nullable|date|after:today',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = auth()->user();
        if ($user->role !== 'buyer') {
            return response()->json(['error' => 'Only buyers can create deals from listings'], 403);
        }

        $listing = FarmerListing::with('product')->findOrFail($request->farmer_listing_id);
        
        if (!$listing->is_active) {
            return response()->json(['error' => 'This listing is no longer active'], 422);
        }

        if ($request->quantity > $listing->quantity) {
            return response()->json(['error' => 'Requested quantity exceeds available quantity'], 422);
        }

        DB::beginTransaction();
        try {
            $deal = Deal::create([
                'farmer_id' => $listing->user_id,
                'buyer_id' => $user->id,
                'product_id' => $listing->product_id,
                'farmer_listing_id' => $listing->id,
                'quantity' => $request->quantity,
                'agreed_price' => $listing->price,
                'total_amount' => $listing->price * $request->quantity,
                'delivery_location' => $request->delivery_location,
                'delivery_date' => $request->delivery_date,
                'buyer_notes' => $request->notes,
                'status' => 'pending',
                'payment_status' => 'unpaid',
            ]);

            // Create notification for farmer
            Notification::create([
                'user_id' => $listing->user_id,
                'type' => 'deal_request',
                'title' => 'New Deal Request',
                'message' => "{$user->name} wants to buy {$request->quantity} {$listing->unit} of your {$listing->product->name}",
                'priority' => 'high',
                'action_url' => "/deals/{$deal->id}",
            ]);

            // Increment inquiries count
            $listing->increment('inquiries_count');

            DB::commit();
            
            return response()->json([
                'message' => 'Deal request sent successfully',
                'deal' => $deal->load(['farmer', 'buyer', 'product', 'farmerListing']),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to create deal: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Farmer creates a deal offer from a buyer request
     */
    public function createFromRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'buyer_request_id' => 'required|exists:buyer_requests,id',
            'quantity' => 'required|numeric|min:0.01',
            'offered_price' => 'required|numeric|min:0',
            'delivery_date' => 'nullable|date|after:today',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = auth()->user();
        if ($user->role !== 'farmer') {
            return response()->json(['error' => 'Only farmers can offer deals'], 403);
        }

        $buyerRequest = BuyerRequest::with('product')->findOrFail($request->buyer_request_id);
        
        if (!$buyerRequest->is_active) {
            return response()->json(['error' => 'This request is no longer active'], 422);
        }

        if ($request->quantity > $buyerRequest->quantity) {
            return response()->json(['error' => 'Offered quantity exceeds requested quantity'], 422);
        }

        DB::beginTransaction();
        try {
            $deal = Deal::create([
                'farmer_id' => $user->id,
                'buyer_id' => $buyerRequest->user_id,
                'product_id' => $buyerRequest->product_id,
                'buyer_request_id' => $buyerRequest->id,
                'quantity' => $request->quantity,
                'agreed_price' => $request->offered_price,
                'total_amount' => $request->offered_price * $request->quantity,
                'delivery_location' => $buyerRequest->location,
                'delivery_date' => $request->delivery_date,
                'farmer_notes' => $request->notes,
                'status' => 'pending',
                'payment_status' => 'unpaid',
            ]);

            // Create notification for buyer
            Notification::create([
                'user_id' => $buyerRequest->user_id,
                'type' => 'deal_offer',
                'title' => 'New Deal Offer',
                'message' => "{$user->name} offered {$request->quantity} {$buyerRequest->unit} of {$buyerRequest->product->name} at KES {$request->offered_price}/{$buyerRequest->unit}",
                'priority' => 'high',
                'action_url' => "/deals/{$deal->id}",
            ]);

            // Increment offers received
            $buyerRequest->increment('offers_received');

            DB::commit();
            
            return response()->json([
                'message' => 'Deal offer sent successfully',
                'deal' => $deal->load(['farmer', 'buyer', 'product', 'buyerRequest']),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to create deal: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update deal status (accept, cancel, mark delivered, etc.)
     */
    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:accepted,cancelled,in_transit,delivered,completed,disputed',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = auth()->user();
        $deal = Deal::with(['farmer', 'buyer', 'product'])->findOrFail($id);

        // Verify user is part of the deal
        if ($deal->farmer_id !== $user->id && $deal->buyer_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Business logic for status transitions
        if ($request->status === 'accepted' && $deal->status !== 'pending') {
            return response()->json(['error' => 'Only pending deals can be accepted'], 422);
        }

        DB::beginTransaction();
        try {
            $deal->status = $request->status;
            
            if ($request->status === 'accepted') {
                $deal->accepted_at = now();
                
                // Reduce quantity from listing or mark request as fulfilled
                if ($deal->farmer_listing_id) {
                    $listing = $deal->farmerListing;
                    $listing->quantity -= $deal->quantity;
                    if ($listing->quantity <= 0) {
                        $listing->is_active = false;
                    }
                    $listing->save();
                }
                
            } elseif ($request->status === 'delivered') {
                $deal->delivered_at = now();
                
            } elseif ($request->status === 'completed') {
                $deal->completed_at = now();
                
                // Update user stats
                User::where('id', $deal->farmer_id)->increment('successful_deals');
                User::where('id', $deal->buyer_id)->increment('successful_deals');
                User::where('id', $deal->farmer_id)->increment('total_deals');
                User::where('id', $deal->buyer_id)->increment('total_deals');
            }

            // Add notes
            if ($request->notes) {
                if ($user->role === 'farmer') {
                    $deal->farmer_notes = $request->notes;
                } else {
                    $deal->buyer_notes = $request->notes;
                }
            }

            $deal->save();

            // Notify other party
            $otherUserId = $user->id === $deal->farmer_id ? $deal->buyer_id : $deal->farmer_id;
            Notification::create([
                'user_id' => $otherUserId,
                'type' => 'deal_status_updated',
                'title' => 'Deal Status Updated',
                'message' => "Deal #{$deal->id} for {$deal->product->name} status changed to {$request->status}",
                'priority' => 'normal',
                'action_url' => "/deals/{$deal->id}",
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Deal status updated successfully',
                'deal' => $deal->fresh(['farmer', 'buyer', 'product']),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to update deal: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update payment status
     */
    public function updatePaymentStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'payment_status' => 'required|in:unpaid,partial,paid,refunded',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = auth()->user();
        $deal = Deal::findOrFail($id);

        // Only buyer and farmer can update payment status
        if ($deal->farmer_id !== $user->id && $deal->buyer_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $deal->payment_status = $request->payment_status;
        $deal->save();

        return response()->json([
            'message' => 'Payment status updated successfully',
            'deal' => $deal->fresh(['farmer', 'buyer', 'product']),
        ]);
    }

    /**
     * Get deal statistics for the authenticated user
     */
    public function statistics()
    {
        $user = auth()->user();

        if ($user->role === 'farmer') {
            $stats = [
                'total_deals' => Deal::where('farmer_id', $user->id)->count(),
                'pending_deals' => Deal::where('farmer_id', $user->id)->where('status', 'pending')->count(),
                'active_deals' => Deal::where('farmer_id', $user->id)->active()->count(),
                'completed_deals' => Deal::where('farmer_id', $user->id)->where('status', 'completed')->count(),
                'total_revenue' => Deal::where('farmer_id', $user->id)
                    ->where('status', 'completed')
                    ->sum('total_amount'),
                'pending_revenue' => Deal::where('farmer_id', $user->id)
                    ->whereIn('status', ['accepted', 'in_transit', 'delivered'])
                    ->sum('total_amount'),
            ];
        } else {
            $stats = [
                'total_deals' => Deal::where('buyer_id', $user->id)->count(),
                'pending_deals' => Deal::where('buyer_id', $user->id)->where('status', 'pending')->count(),
                'active_deals' => Deal::where('buyer_id', $user->id)->active()->count(),
                'completed_deals' => Deal::where('buyer_id', $user->id)->where('status', 'completed')->count(),
                'total_spent' => Deal::where('buyer_id', $user->id)
                    ->where('status', 'completed')
                    ->sum('total_amount'),
                'pending_payments' => Deal::where('buyer_id', $user->id)
                    ->whereIn('status', ['accepted', 'in_transit', 'delivered'])
                    ->where('payment_status', '!=', 'paid')
                    ->sum('total_amount'),
            ];
        }

        return response()->json($stats);
    }
}
