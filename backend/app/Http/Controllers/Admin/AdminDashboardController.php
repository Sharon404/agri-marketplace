<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Deal;
use App\Models\FarmerListing;
use App\Models\BuyerRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminDashboardController extends Controller
{
    /**
     * Show admin dashboard home.
     */
    public function index()
    {
        $stats = [
            'total_users' => User::where('role', '!=', 'admin')->count(),
            'pending_approvals' => User::where('approval_status', 'pending')->count(),
            'approved_users' => User::where('approval_status', 'approved')->count(),
            'total_farmers' => User::where('role', 'farmer')->count(),
            'total_buyers' => User::where('role', 'buyer')->count(),
            'active_deals' => Deal::whereIn('status', ['pending', 'accepted'])->count(),
            'active_listings' => FarmerListing::where('is_active', true)->count(),
            'active_requests' => BuyerRequest::where('is_active', true)->count(),
        ];

        return view('admin.dashboard', compact('stats'));
    }

    /**
     * Show pending users for approval.
     */
    public function pendingUsers(Request $request)
    {
        $role = $request->query('role');
        
        $query = User::where('approval_status', 'pending');
        
        if ($role && in_array($role, ['farmer', 'buyer'])) {
            $query->where('role', $role);
        }
        
        $users = $query->orderBy('created_at', 'desc')->paginate(20);
        
        return view('admin.users.pending', compact('users'));
    }

    /**
     * Show approved users.
     */
    public function approvedUsers(Request $request)
    {
        $role = $request->query('role');
        
        $query = User::where('approval_status', 'approved');
        
        if ($role && in_array($role, ['farmer', 'buyer'])) {
            $query->where('role', $role);
        }
        
        $users = $query->orderBy('approved_at', 'desc')->paginate(20);
        
        return view('admin.users.approved', compact('users'));
    }

    /**
     * Show rejected users.
     */
    public function rejectedUsers(Request $request)
    {
        $role = $request->query('role');
        
        $query = User::where('approval_status', 'rejected');
        
        if ($role && in_array($role, ['farmer', 'buyer'])) {
            $query->where('role', $role);
        }
        
        $users = $query->orderBy('approved_at', 'desc')->paginate(20);
        
        return view('admin.users.rejected', compact('users'));
    }

    /**
     * Approve a user.
     */
    public function approveUser(Request $request, User $user)
    {
        try {
            if ($user->approval_status === 'approved') {
                return redirect()->back()->with('message', 'User is already approved');
            }
            
            $user->approve(auth()->user());
            
            Log::info("User {$user->id} ({$user->email}) approved by admin " . auth()->id());
            
            return redirect()->back()->with('success', 'User approved successfully');
        } catch (\Exception $e) {
            Log::error('User approval error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to approve user');
        }
    }

    /**
     * Reject a user.
     */
    public function rejectUser(Request $request, User $user)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);
        
        try {
            if ($user->approval_status === 'rejected') {
                return redirect()->back()->with('message', 'User is already rejected');
            }
            
            $user->reject($request->reason, auth()->user());
            
            Log::info("User {$user->id} ({$user->email}) rejected by admin " . auth()->id());
            
            return redirect()->back()->with('success', 'User rejected successfully');
        } catch (\Exception $e) {
            Log::error('User rejection error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to reject user');
        }
    }

    /**
     * Show deals.
     */
    public function deals(Request $request)
    {
        $status = $request->query('status');
        
        $query = Deal::with(['farmer', 'buyer', 'product']);
        
        if ($status) {
            $query->where('status', $status);
        }
        
        $deals = $query->orderBy('created_at', 'desc')->paginate(20);
        
        return view('admin.deals.index', compact('deals'));
    }

    /**
     * Create a new deal.
     */
    public function createDeal(Request $request)
    {
        $request->validate([
            'farmer_id' => 'required|exists:users,id',
            'buyer_id' => 'required|exists:users,id',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|numeric|min:0.01',
            'price' => 'required|numeric|min:0.01',
        ]);
        
        try {
            $deal = Deal::create([
                'farmer_id' => $request->farmer_id,
                'buyer_id' => $request->buyer_id,
                'product_id' => $request->product_id,
                'quantity' => $request->quantity,
                'unit_price' => $request->price,
                'total_price' => $request->quantity * $request->price,
                'status' => 'pending',
                'created_by_admin' => true,
            ]);
            
            return redirect()->back()->with('success', 'Deal created successfully');
        } catch (\Exception $e) {
            Log::error('Deal creation error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to create deal');
        }
    }

    /**
     * Update deal status.
     */
    public function updateDealStatus(Request $request, Deal $deal)
    {
        $request->validate([
            'status' => 'required|in:pending,accepted,in_transit,delivered,completed,cancelled,disputed',
        ]);
        
        try {
            $deal->update(['status' => $request->status]);
            
            return redirect()->back()->with('success', 'Deal status updated successfully');
        } catch (\Exception $e) {
            Log::error('Deal update error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to update deal');
        }
    }

    /**
     * Show analytics.
     */
    public function analytics()
    {
        return view('admin.analytics');
    }

    /**
     * Get analytics data (JSON).
     */
    public function analyticsData()
    {
        $data = [
            'total_users' => User::where('role', '!=', 'admin')->count(),
            'approved_users' => User::where('approval_status', 'approved')->count(),
            'pending_users' => User::where('approval_status', 'pending')->count(),
            'rejected_users' => User::where('approval_status', 'rejected')->count(),
            'farmers' => User::where('role', 'farmer')->count(),
            'buyers' => User::where('role', 'buyer')->count(),
            'listings' => FarmerListing::where('is_active', true)->count(),
            'requests' => BuyerRequest::where('is_active', true)->count(),
            'deals' => Deal::count(),
        ];
        
        return response()->json($data);
    }
}
