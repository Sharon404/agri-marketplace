<?php

namespace App\Http\Controllers\Admin;

use App\Events\CapabilityApproved;
use App\Models\User;
use App\Models\UserCapability;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CapabilityController extends Controller
{
    /**
     * Display all capability requests (pending, approved, rejected).
     */
    public function index(Request $request): View|JsonResponse
    {
        $query = UserCapability::with('user');

        // Filter by capability type
        if ($request->has('type') && in_array($request->query('type'), ['buy', 'sell', 'all'])) {
            $type = $request->query('type');
            if ($type !== 'all') {
                $column = $type === 'buy' ? 'buy_requested_at' : 'sell_requested_at';
                $query->whereNotNull($column);
            }
        }

        // Filter by status
        if ($request->has('status')) {
            $status = $request->query('status');
            if (in_array($status, ['pending', 'approved', 'rejected'])) {
                if ($status === 'pending') {
                    // Pending = requested but not approved
                    $query->where(function ($q) {
                        $q->where(function ($subQ) {
                            $subQ->whereNotNull('buy_requested_at')->whereNull('buy_approved_at');
                        })->orWhere(function ($subQ) {
                            $subQ->whereNotNull('sell_requested_at')->whereNull('sell_approved_at');
                        });
                    });
                } elseif ($status === 'approved') {
                    // Approved = both buy and sell approved, or at least one
                    $query->where(function ($q) {
                        $q->whereNotNull('buy_approved_at')->orWhereNotNull('sell_approved_at');
                    });
                } elseif ($status === 'rejected') {
                    // Rejected = requested but has rejection (we need to add rejection fields)
                    // For now, we'll implement basic rejection logic
                    $query->where('status', 'rejected');
                }
            }
        }

        $capabilities = $query->paginate(20);

        // Check if API request
        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $capabilities,
                'message' => 'Capability requests retrieved successfully',
            ]);
        }

        // Return Blade view with Velzon theme
        return view('admin.capabilities.index', compact('capabilities'));
    }

    /**
     * Approve buy capability for a user.
     */
    public function approveBuy(Request $request, User $user): JsonResponse
    {
        return $this->approveCapability($user, 'buy', $request);
    }

    /**
     * Approve sell capability for a user.
     */
    public function approveSell(Request $request, User $user): JsonResponse
    {
        return $this->approveCapability($user, 'sell', $request);
    }

    /**
     * Reject buy capability for a user.
     */
    public function rejectBuy(Request $request, User $user): JsonResponse
    {
        return $this->rejectCapability($user, 'buy', $request);
    }

    /**
     * Reject sell capability for a user.
     */
    public function rejectSell(Request $request, User $user): JsonResponse
    {
        return $this->rejectCapability($user, 'sell', $request);
    }

    /**
     * Common logic for approving capabilities.
     */
    private function approveCapability(User $user, string $type, Request $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $capability = $user->capability;

            if (!$capability) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'User capability record not found',
                ], 404);
            }

            // Check if already approved
            $column = $type === 'buy' ? 'buy_approved_at' : 'sell_approved_at';
            $requestColumn = $type === 'buy' ? 'buy_requested_at' : 'sell_requested_at';

            if ($capability->{$column} !== null) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => ucfirst($type) . ' capability already approved',
                ], 422);
            }

            if ($capability->{$requestColumn} === null) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'No pending ' . $type . ' capability request found',
                ], 422);
            }

            // Update capability
            if ($type === 'buy') {
                $capability->can_buy = true;
                $capability->buy_approved_at = now();
            } else {
                $capability->can_sell = true;
                $capability->sell_approved_at = now();
            }

            $capability->status = 'active';
            $capability->save();

            DB::commit();

            // Fire event
            event(new CapabilityApproved(
                user: $user,
                capability: $capability,
                capabilityType: $type,
                approvedBy: auth()->user(),
            ));

            // Log audit
            $this->logAudit($user, $type, 'approved');

            return response()->json([
                'success' => true,
                'message' => ucfirst($type) . ' capability approved successfully',
                'data' => [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'capability_type' => $type,
                    'approved_at' => $capability->{$column},
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Capability approval error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to approve capability: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Common logic for rejecting capabilities.
     */
    private function rejectCapability(User $user, string $type, Request $request): JsonResponse
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $capability = $user->capability;

            if (!$capability) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'User capability record not found',
                ], 404);
            }

            $requestColumn = $type === 'buy' ? 'buy_requested_at' : 'sell_requested_at';

            if ($capability->{$requestColumn} === null) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'No pending ' . $type . ' capability request found',
                ], 422);
            }

            // Mark as rejected (clear the request, set status)
            if ($type === 'buy') {
                $capability->buy_requested_at = null;
            } else {
                $capability->sell_requested_at = null;
            }

            $capability->status = 'rejected';
            $capability->save();

            DB::commit();

            // Log audit
            $this->logAudit($user, $type, 'rejected', $request->input('reason'));

            return response()->json([
                'success' => true,
                'message' => ucfirst($type) . ' capability request rejected',
                'data' => [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'capability_type' => $type,
                    'rejection_reason' => $request->input('reason'),
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Capability rejection error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to reject capability: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Log capability approval/rejection to audit logs.
     */
    private function logAudit(User $user, string $type, string $action, ?string $reason = null): void
    {
        try {
            \App\Models\AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'capability_' . $action,
                'model_type' => 'UserCapability',
                'model_id' => $user->id,
                'changes' => [
                    'type' => $type,
                    'action' => $action,
                    'reason' => $reason,
                    'approved_by' => auth()->user()->name,
                    'timestamp' => now()->toDateTimeString(),
                ],
            ]);
        } catch (\Exception $e) {
            \Log::warning('Failed to log capability audit: ' . $e->getMessage());
        }
    }
}
