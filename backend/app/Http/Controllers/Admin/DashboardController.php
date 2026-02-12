<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\BuyerRequest;
use App\Models\Deal;
use App\Models\DeliveryVerification;
use App\Models\Dispute;
use App\Models\FarmerListing;
use App\Models\LogisticsJob;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // Capability-based metrics with optimized queries
        $stats = [
            'total_users' => User::count(),
            
            // Capability-based counts (replaces role-based)
            'verified_sellers' => User::whereHas('capability', function ($q) {
                $q->where('can_sell', true)->where('status', 'active');
            })->count(),
            
            'verified_buyers' => User::whereHas('capability', function ($q) {
                $q->where('can_buy', true)->where('status', 'active');
            })->count(),
            
            // Pending capability requests
            'pending_seller_requests' => User::whereHas('capability', function ($q) {
                $q->whereNotNull('sell_requested_at')
                  ->whereNull('sell_approved_at');
            })->count(),
            
            'pending_buyer_requests' => User::whereHas('capability', function ($q) {
                $q->whereNotNull('buy_requested_at')
                  ->whereNull('buy_approved_at');
            })->count(),
            
            // Legacy role counts (kept for comparison during transition)
            'total_farmers_by_role' => User::where('role', 'farmer')->count(),
            'total_buyers_by_role' => User::where('role', 'buyer')->count(),
            
            'active_listings' => FarmerListing::active()->count(),
            'active_requests' => BuyerRequest::active()->count(),
            'pending_deals' => Deal::where('status', 'pending')->count(),
            'active_deals' => Deal::whereIn('status', ['accepted', 'logistics_assigned', 'delivered'])->count(),
            'completed_deals' => Deal::where('status', 'completed')->count(),
            
            // Check if transactions table exists before querying
            'held_funds' => \Schema::hasTable('transactions') 
                ? Transaction::where('status', 'held')->sum('amount') 
                : 0,
            
            'total_disputes' => \Schema::hasTable('disputes')
                ? Dispute::where('status', '!=', 'closed')->count()
                : 0,
        ];

        return response()->json([
            'message' => 'Admin dashboard data',
            'stats' => $stats,
        ]);
    }

    public function users(Request $request)
    {
        $query = User::query();

        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        if ($request->has('verified')) {
            if ($request->verified == 'email') {
                $query->where('email_verified', true);
            } elseif ($request->verified == 'phone') {
                $query->where('phone_verified', true);
            }
        }

        $users = $query->paginate(20);

        return response()->json([
            'users' => $users,
        ]);
    }

    public function verifyUser(Request $request, User $user)
    {
        $request->validate([
            'type' => 'required|in:email,phone',
        ]);

        if ($request->type === 'email') {
            $user->update(['email_verified' => true]);
        } else {
            $user->update(['phone_verified' => true]);
        }

        AuditLog::log(auth()->user(), 'verify_user', $user, [], [
            'verification_type' => $request->type,
        ]);

        return response()->json([
            'message' => 'User verified successfully',
            'user' => $user,
        ]);
    }

    public function deals(Request $request)
    {
        $query = Deal::with(['farmerListing.product', 'buyerRequest.product', 'broker']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $deals = $query->paginate(20);

        return response()->json([
            'deals' => $deals,
        ]);
    }

    public function createDeal(Request $request)
    {
        $request->validate([
            'farmer_listing_id' => 'required|exists:farmer_listings,id',
            'buyer_request_id' => 'required|exists:buyer_requests,id',
            'agreed_quantity' => 'required|numeric|min:0.01',
            'agreed_price' => 'required|numeric|min:0.01',
        ]);

        $farmerListing = FarmerListing::findOrFail($request->farmer_listing_id);
        $buyerRequest = BuyerRequest::findOrFail($request->buyer_request_id);

        // Check if deal already exists
        $existingDeal = Deal::where('farmer_listing_id', $request->farmer_listing_id)
            ->where('buyer_request_id', $request->buyer_request_id)
            ->first();

        if ($existingDeal) {
            return response()->json(['error' => 'Deal already exists for this listing and request'], 422);
        }

        $deal = Deal::create([
            'farmer_listing_id' => $request->farmer_listing_id,
            'buyer_request_id' => $request->buyer_request_id,
            'broker_id' => auth()->id(),
            'agreed_quantity' => $request->agreed_quantity,
            'agreed_price' => $request->agreed_price,
        ]);

        AuditLog::log(auth()->user(), 'create_deal', $deal);

        return response()->json([
            'message' => 'Deal created successfully',
            'deal' => $deal->load(['farmerListing.product', 'buyerRequest.product', 'broker']),
        ], 201);
    }

    public function updateDealStatus(Request $request, Deal $deal)
    {
        $request->validate([
            'status' => 'required|in:pending,negotiated,accepted,logistics_assigned,delivered,completed,cancelled',
        ]);

        if (!$deal->canTransitionTo($request->status)) {
            return response()->json(['error' => 'Invalid status transition'], 422);
        }

        $oldStatus = $deal->status;
        $deal->transitionTo($request->status);

        AuditLog::log(auth()->user(), 'update_deal_status', $deal, [
            'status' => $oldStatus,
        ], [
            'status' => $request->status,
        ]);

        return response()->json([
            'message' => 'Deal status updated successfully',
            'deal' => $deal->load(['farmerListing.product', 'buyerRequest.product', 'broker']),
        ]);
    }

    public function transactions(Request $request)
    {
        $query = Transaction::with(['deal.farmerListing.product', 'deal.buyerRequest.product']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $transactions = $query->paginate(20);

        return response()->json([
            'transactions' => $transactions,
        ]);
    }

    public function releaseFunds(Request $request, Transaction $transaction)
    {
        if ($transaction->release()) {
            AuditLog::log(auth()->user(), 'release_funds', $transaction);

            return response()->json([
                'message' => 'Funds released successfully',
                'transaction' => $transaction,
            ]);
        }

        return response()->json(['error' => 'Unable to release funds'], 422);
    }

    public function refundFunds(Request $request, Transaction $transaction)
    {
        if ($transaction->refund()) {
            AuditLog::log(auth()->user(), 'refund_funds', $transaction);

            return response()->json([
                'message' => 'Funds refunded successfully',
                'transaction' => $transaction,
            ]);
        }

        return response()->json(['error' => 'Unable to refund funds'], 422);
    }

    public function logisticsJobs(Request $request)
    {
        $query = LogisticsJob::with(['deal.farmerListing.product', 'deal.buyerRequest.product', 'assignedAgent']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $jobs = $query->paginate(20);

        return response()->json([
            'logistics_jobs' => $jobs,
        ]);
    }

    public function assignLogistics(Request $request, LogisticsJob $job)
    {
        $request->validate([
            'assigned_agent_id' => 'required|exists:users,id',
            'scheduled_pickup_at' => 'required|date',
            'scheduled_delivery_at' => 'required|date|after:scheduled_pickup_at',
            'pickup_location' => 'required|string',
            'delivery_location' => 'required|string',
        ]);

        $job->update([
            'assigned_agent_id' => $request->assigned_agent_id,
            'scheduled_pickup_at' => $request->scheduled_pickup_at,
            'scheduled_delivery_at' => $request->scheduled_delivery_at,
            'pickup_location' => $request->pickup_location,
            'delivery_location' => $request->delivery_location,
            'status' => 'assigned',
        ]);

        AuditLog::log(auth()->user(), 'assign_logistics', $job);

        return response()->json([
            'message' => 'Logistics job assigned successfully',
            'job' => $job->load(['deal', 'assignedAgent']),
        ]);
    }

    public function completeDelivery(Request $request, LogisticsJob $job)
    {
        $request->validate([
            'verification_data' => 'required|array',
        ]);

        DB::transaction(function () use ($job, $request) {
            $job->update([
                'status' => 'delivered',
                'actual_delivery_at' => now(),
            ]);

            DeliveryVerification::create([
                'logistics_job_id' => $job->id,
                'verified_by_id' => auth()->id(),
                'verification_type' => 'admin_verification',
                'verification_data' => $request->verification_data,
                'verified_at' => now(),
            ]);

            // Update deal status
            $job->deal->transitionTo('delivered');
        });

        AuditLog::log(auth()->user(), 'complete_delivery', $job);

        return response()->json([
            'message' => 'Delivery completed successfully',
            'job' => $job->load(['deal', 'assignedAgent']),
        ]);
    }

    public function disputes(Request $request)
    {
        $query = Dispute::with(['deal.farmerListing.product', 'deal.buyerRequest.product', 'raisedBy', 'resolvedBy']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $disputes = $query->paginate(20);

        return response()->json([
            'disputes' => $disputes,
        ]);
    }

    public function resolveDispute(Request $request, Dispute $dispute)
    {
        $request->validate([
            'resolution' => 'required|string',
        ]);

        if ($dispute->resolve(auth()->user(), $request->resolution)) {
            AuditLog::log(auth()->user(), 'resolve_dispute', $dispute, [], [
                'resolution' => $request->resolution,
            ]);

            return response()->json([
                'message' => 'Dispute resolved successfully',
                'dispute' => $dispute->load(['deal', 'raisedBy', 'resolvedBy']),
            ]);
        }

        return response()->json(['error' => 'Unable to resolve dispute'], 422);
    }

    public function auditLogs(Request $request)
    {
        $query = AuditLog::with(['user']);

        if ($request->has('action')) {
            $query->where('action', $request->action);
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('created_at', '<=', $request->date_to);
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate(50);

        return response()->json([
            'audit_logs' => $logs,
        ]);
    }
}
