<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Deal;
use App\Models\Payment;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class DealsController extends Controller
{
    use AuthorizesRequests;
    
    /**
     * Get all deals for the authenticated user
     * 
     * MANAGED MARKETPLACE: Users can only view deals created by admin.
     * Farmers can view deals they're involved in. Buyers can view deals they're involved in.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $status = $request->query('status');

        $query = Deal::with(['farmer', 'buyer', 'product', 'farmerSupply', 'payment'])
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
        $deal = Deal::with(['farmer', 'buyer', 'product', 'farmerSupply', 'payment', 'reviews'])
            ->findOrFail($id);

        // Verify user is part of the deal
        if ($deal->farmer_id !== $user->id && $deal->buyer_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json($deal);
    }

    /**
     * Buyer or Farmer accepts a deal created by admin
     * 
     * MANAGED MARKETPLACE: Only the relevant party can accept.
     * - Buyer can accept when status = pending_buyer_confirmation
     * - Farmer can accept when status = pending_farmer_confirmation
     * 
     * When both parties have confirmed, deal moves to 'both_confirmed'
     * and Payment record is automatically created.
     */
    public function accept(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = auth()->user();
        $deal = Deal::with(['farmer', 'buyer', 'product', 'payment'])->findOrFail($id);

        // Verify user is part of the deal
        if ($deal->farmer_id !== $user->id && $deal->buyer_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized - not part of this deal'], 403);
        }

        DB::beginTransaction();
        try {
            $isBuyer = $user->id === $deal->buyer_id;
            $isFarmer = $user->id === $deal->farmer_id;

            // Validate current status and user role
            if ($isBuyer && $deal->status !== 'pending_buyer_confirmation') {
                return response()->json([
                    'error' => 'Invalid deal status for buyer acceptance',
                    'current_status' => $deal->status,
                    'expected_status' => 'pending_buyer_confirmation'
                ], 422);
            }

            if ($isFarmer && $deal->status !== 'pending_farmer_confirmation') {
                return response()->json([
                    'error' => 'Invalid deal status for farmer acceptance',
                    'current_status' => $deal->status,
                    'expected_status' => 'pending_farmer_confirmation'
                ], 422);
            }

            // Record confirmation
            if ($isBuyer) {
                $deal->buyer_confirmed_at = now();
                
                // Add notes if buyer provided
                if ($request->notes) {
                    $deal->buyer_notes = $request->notes;
                }

                // Move to farmer confirmation stage if not already confirmed
                if (!$deal->farmer_confirmed_at) {
                    $deal->status = 'pending_farmer_confirmation';
                } else {
                    // Farmer already confirmed, move to both_confirmed
                    $deal->status = 'both_confirmed';
                }
            } else {
                $deal->farmer_confirmed_at = now();
                
                // Add notes if farmer provided
                if ($request->notes) {
                    $deal->farmer_notes = $request->notes;
                }

                // Move to buyer confirmation stage if not already confirmed
                if (!$deal->buyer_confirmed_at) {
                    $deal->status = 'pending_buyer_confirmation';
                } else {
                    // Buyer already confirmed, move to both_confirmed
                    $deal->status = 'both_confirmed';
                }
            }

            $deal->save();

            // If both parties confirmed, create Payment record
            if ($deal->status === 'both_confirmed' && !$deal->payment) {
                Payment::create([
                    'deal_id' => $deal->id,
                    'buyer_id' => $deal->buyer_id,
                    'amount' => $deal->total_amount,
                    'status' => 'pending',
                    'payment_method' => 'mpesa', // Default, can be changed by buyer
                ]);

                $deal->status = 'payment_pending';
                $deal->save();

                // Notify buyer to proceed with payment
                Notification::create([
                    'user_id' => $deal->buyer_id,
                    'type' => 'payment_required',
                    'title' => 'Payment Required',
                    'message' => "Deal #{$deal->id} is confirmed. Please proceed with payment of KES {$deal->total_amount}",
                    'priority' => 'high',
                    'action_url' => "/deals/{$deal->id}/pay",
                ]);
            } else {
                // Notify the other party of acceptance
                $otherUserId = $isBuyer ? $deal->farmer_id : $deal->buyer_id;
                $userRole = $isBuyer ? 'Buyer' : 'Farmer';
                
                Notification::create([
                    'user_id' => $otherUserId,
                    'type' => 'deal_accepted',
                    'title' => 'Deal Accepted',
                    'message' => "{$userRole} has accepted deal #{$deal->id}",
                    'priority' => 'high',
                    'action_url' => "/deals/{$deal->id}",
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Deal accepted successfully',
                'deal' => $deal->fresh(['farmer', 'buyer', 'product', 'payment']),
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to accept deal: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Buyer or Farmer rejects a deal before both confirm
     * 
     * MANAGED MARKETPLACE: Can only reject in confirmation stages.
     * Deal moves to 'rejected' status and deal is cancelled.
     */
    public function reject(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = auth()->user();
        $deal = Deal::with(['farmer', 'buyer', 'product', 'payment'])->findOrFail($id);

        // Verify user is part of the deal
        if ($deal->farmer_id !== $user->id && $deal->buyer_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized - not part of this deal'], 403);
        }

        // Can only reject if both haven't confirmed yet
        if ($deal->status === 'both_confirmed' || $deal->status === 'payment_pending' || 
            $deal->status === 'accepted' || $deal->status === 'completed' || 
            $deal->status === 'rejected' || $deal->status === 'cancelled') {
            
            return response()->json([
                'error' => 'Cannot reject deal in current status',
                'current_status' => $deal->status
            ], 422);
        }

        DB::beginTransaction();
        try {
            $isBuyer = $user->id === $deal->buyer_id;
            $userRole = $isBuyer ? 'Buyer' : 'Farmer';

            // Update deal status to rejected
            $deal->status = 'rejected';
            $deal->save();

            // Notify the other party
            $otherUserId = $isBuyer ? $deal->farmer_id : $deal->buyer_id;
            Notification::create([
                'user_id' => $otherUserId,
                'type' => 'deal_rejected',
                'title' => 'Deal Rejected',
                'message' => "{$userRole} has rejected deal #{$deal->id}" . ($request->reason ? ": {$request->reason}" : ""),
                'priority' => 'normal',
                'action_url' => "/deals/{$deal->id}",
            ]);

            // Notify admin
            $admins = User::where('role', 'admin')->get();
            foreach ($admins as $admin) {
                Notification::create([
                    'user_id' => $admin->id,
                    'type' => 'deal_rejected',
                    'title' => 'Deal Rejected by User',
                    'message' => "Deal #{$deal->id} was rejected by {$userRole}",
                    'priority' => 'normal',
                    'action_url' => "/admin/deals/{$deal->id}",
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Deal rejected successfully',
                'deal' => $deal->fresh(['farmer', 'buyer', 'product']),
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to reject deal: ' . $e->getMessage()], 500);
        }
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
                'pending_confirmation' => Deal::where('farmer_id', $user->id)
                    ->where('status', 'pending_farmer_confirmation')->count(),
                'awaiting_payment' => Deal::where('farmer_id', $user->id)
                    ->where('status', 'payment_pending')->count(),
                'active_deals' => Deal::where('farmer_id', $user->id)
                    ->whereIn('status', ['accepted', 'in_transit', 'delivered'])->count(),
                'completed_deals' => Deal::where('farmer_id', $user->id)
                    ->where('status', 'completed')->count(),
                'rejected_deals' => Deal::where('farmer_id', $user->id)
                    ->where('status', 'rejected')->count(),
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
                'pending_confirmation' => Deal::where('buyer_id', $user->id)
                    ->where('status', 'pending_buyer_confirmation')->count(),
                'awaiting_payment' => Deal::where('buyer_id', $user->id)
                    ->where('status', 'payment_pending')->count(),
                'active_deals' => Deal::where('buyer_id', $user->id)
                    ->whereIn('status', ['accepted', 'in_transit', 'delivered'])->count(),
                'completed_deals' => Deal::where('buyer_id', $user->id)
                    ->where('status', 'completed')->count(),
                'rejected_deals' => Deal::where('buyer_id', $user->id)
                    ->where('status', 'rejected')->count(),
                'total_spent' => Deal::where('buyer_id', $user->id)
                    ->where('status', 'completed')
                    ->sum('total_amount'),
                'pending_payments' => Deal::where('buyer_id', $user->id)
                    ->where('status', 'payment_pending')
                    ->sum('total_amount'),
            ];
        }

        return response()->json($stats);
    }
}
