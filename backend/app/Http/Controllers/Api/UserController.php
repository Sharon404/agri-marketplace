<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    /**
     * Get authenticated user's capability status.
     * 
     * Returns capability information for mode switching without breaking existing API.
     * 
     * @return JsonResponse
     */
    public function getCapabilities(): JsonResponse
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        try {
            // Get or create capability record
            $capability = $user->getOrCreateCapability();

            // Determine buy status
            $buyStatus = 'none';
            if ($capability->buy_approved_at !== null) {
                $buyStatus = 'approved';
            } elseif ($capability->buy_requested_at !== null && $capability->buy_approved_at === null) {
                $buyStatus = 'pending';
            }

            // Determine sell status
            $sellStatus = 'none';
            if ($capability->sell_approved_at !== null) {
                $sellStatus = 'approved';
            } elseif ($capability->sell_requested_at !== null && $capability->sell_approved_at === null) {
                $sellStatus = 'pending';
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'can_buy' => (bool) $capability->can_buy,
                    'can_sell' => (bool) $capability->can_sell,
                    'buy_status' => $buyStatus,
                    'sell_status' => $sellStatus,
                ],
                'message' => 'User capabilities retrieved successfully',
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to get user capabilities: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve capabilities',
            ], 500);
        }
    }
}
