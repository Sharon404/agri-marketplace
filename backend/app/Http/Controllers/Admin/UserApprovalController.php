<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserApprovalController extends Controller
{
    /**
     * Get pending user approvals for admin dashboard.
     */
    public function pendingUsers(Request $request)
    {
        $user = auth()->user();

        // Ensure user is admin
        if ($user && $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Unauthorized'
            ], 403);
        }

        // Get pending users with pagination
        $perPage = $request->input('per_page', 20);
        $role = $request->input('role'); // Optional filter by role

        $query = User::where('approval_status', 'pending');

        if ($role && in_array($role, ['farmer', 'buyer'])) {
            $query->where('role', $role);
        }

        $pendingUsers = $query
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $pendingUsers,
            'message' => null
        ]);
    }

    /**
     * Get approved users.
     */
    public function approvedUsers(Request $request)
    {
        $user = auth()->user();

        // Ensure user is admin
        if ($user && $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Unauthorized'
            ], 403);
        }

        $perPage = $request->input('per_page', 20);
        $role = $request->input('role');

        $query = User::where('approval_status', 'approved');

        if ($role && in_array($role, ['farmer', 'buyer'])) {
            $query->where('role', $role);
        }

        $approvedUsers = $query
            ->orderBy('approved_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $approvedUsers,
            'message' => null
        ]);
    }

    /**
     * Get rejected users.
     */
    public function rejectedUsers(Request $request)
    {
        $user = auth()->user();

        // Ensure user is admin
        if ($user && $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Unauthorized'
            ], 403);
        }

        $perPage = $request->input('per_page', 20);
        $role = $request->input('role');

        $query = User::where('approval_status', 'rejected');

        if ($role && in_array($role, ['farmer', 'buyer'])) {
            $query->where('role', $role);
        }

        $rejectedUsers = $query
            ->orderBy('approved_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $rejectedUsers,
            'message' => null
        ]);
    }

    /**
     * Approve a user.
     */
    public function approve(Request $request, User $user)
    {
        $admin = auth()->user();

        // Ensure user is admin
        if ($admin && $admin->role !== 'admin') {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Unauthorized'
            ], 403);
        }

        try {
            // Check if user is already approved
            if ($user->approval_status === 'approved') {
                return response()->json([
                    'success' => false,
                    'data' => null,
                    'message' => 'User is already approved'
                ], 400);
            }

            // Approve user
            $user->approve($admin);

            Log::info("User {$user->id} ({$user->email}) approved by admin {$admin->id}");

            return response()->json([
                'success' => true,
                'data' => $user,
                'message' => 'User approved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('User approval error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Failed to approve user: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reject a user.
     */
    public function reject(Request $request, User $user)
    {
        $admin = auth()->user();

        // Ensure user is admin
        if ($admin && $admin->role !== 'admin') {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Unauthorized'
            ], 403);
        }

        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            // Check if user is already rejected
            if ($user->approval_status === 'rejected') {
                return response()->json([
                    'success' => false,
                    'data' => null,
                    'message' => 'User is already rejected'
                ], 400);
            }

            // Reject user
            $user->reject($request->reason, $admin);

            Log::info("User {$user->id} ({$user->email}) rejected by admin {$admin->id}. Reason: {$request->reason}");

            return response()->json([
                'success' => true,
                'data' => $user,
                'message' => 'User rejected successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('User rejection error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Failed to reject user: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get approval statistics.
     */
    public function statistics(Request $request)
    {
        $user = auth()->user();

        // Ensure user is admin
        if ($user && $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Unauthorized'
            ], 403);
        }

        try {
            $stats = [
                'total_users' => User::where('role', '!=', 'admin')->count(),
                'pending_approvals' => User::where('approval_status', 'pending')->count(),
                'approved_users' => User::where('approval_status', 'approved')->count(),
                'rejected_users' => User::where('approval_status', 'rejected')->count(),
                'farmers' => [
                    'total' => User::where('role', 'farmer')->count(),
                    'pending' => User::where('role', 'farmer')->where('approval_status', 'pending')->count(),
                    'approved' => User::where('role', 'farmer')->where('approval_status', 'approved')->count(),
                    'rejected' => User::where('role', 'farmer')->where('approval_status', 'rejected')->count(),
                ],
                'buyers' => [
                    'total' => User::where('role', 'buyer')->count(),
                    'pending' => User::where('role', 'buyer')->where('approval_status', 'pending')->count(),
                    'approved' => User::where('role', 'buyer')->where('approval_status', 'approved')->count(),
                    'rejected' => User::where('role', 'buyer')->where('approval_status', 'rejected')->count(),
                ],
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => null
            ]);
        } catch (\Exception $e) {
            Log::error('Approval statistics error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Failed to fetch statistics: ' . $e->getMessage()
            ], 500);
        }
    }
}
