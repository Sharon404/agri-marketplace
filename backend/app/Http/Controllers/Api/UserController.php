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

    /**
     * Request a new capability (buy or sell).
     * 
     * Creates or updates a capability request for admin approval.
     * If already approved, returns current status.
     * If pending, returns pending status.
     * If none, creates new request marked for approval.
     * 
     * @param \Illuminate\Http\Request $request
     * @return JsonResponse
     */
    public function requestCapability(\Illuminate\Http\Request $request): JsonResponse
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        try {
            // Validate request
            $request->validate([
                'capability' => 'required|in:buy,sell',
            ]);

            $capabilityType = $request->input('capability');

            // Get or create capability record
            $capability = $user->getOrCreateCapability();

            // Check current status
            if ($capabilityType === 'buy') {
                // Is already approved?
                if ($capability->buy_approved_at !== null && $capability->can_buy) {
                    return response()->json([
                        'success' => true,
                        'data' => [
                            'capability' => 'buy',
                            'status' => 'approved',
                            'message' => 'You already have buy capability approved',
                        ],
                        'message' => 'Buy capability already approved',
                    ]);
                }

                // Is already pending?
                if ($capability->buy_requested_at !== null && $capability->buy_approved_at === null) {
                    return response()->json([
                        'success' => true,
                        'data' => [
                            'capability' => 'buy',
                            'status' => 'pending',
                            'message' => 'Your buy capability request is under review',
                            'requested_at' => $capability->buy_requested_at,
                        ],
                        'message' => 'Buy capability request is pending',
                    ]);
                }

                // Not requested yet - create new request
                $capability->buy_requested_at = now();
                $capability->save();

                \Log::info("User {$user->id} ({$user->email}) requested buy capability");

                return response()->json([
                    'success' => true,
                    'data' => [
                        'capability' => 'buy',
                        'status' => 'requested',
                        'message' => 'Buy capability request submitted. Admin will review shortly.',
                        'requested_at' => $capability->buy_requested_at,
                    ],
                    'message' => 'Buy capability request submitted successfully',
                ], 201);
            } else {
                // Sell capability request
                // Is already approved?
                if ($capability->sell_approved_at !== null && $capability->can_sell) {
                    return response()->json([
                        'success' => true,
                        'data' => [
                            'capability' => 'sell',
                            'status' => 'approved',
                            'message' => 'You already have sell capability approved',
                        ],
                        'message' => 'Sell capability already approved',
                    ]);
                }

                // Is already pending?
                if ($capability->sell_requested_at !== null && $capability->sell_approved_at === null) {
                    return response()->json([
                        'success' => true,
                        'data' => [
                            'capability' => 'sell',
                            'status' => 'pending',
                            'message' => 'Your sell capability request is under review',
                            'requested_at' => $capability->sell_requested_at,
                        ],
                        'message' => 'Sell capability request is pending',
                    ]);
                }

                // Not requested yet - create new request
                $capability->sell_requested_at = now();
                $capability->save();

                \Log::info("User {$user->id} ({$user->email}) requested sell capability");

                return response()->json([
                    'success' => true,
                    'data' => [
                        'capability' => 'sell',
                        'status' => 'requested',
                        'message' => 'Sell capability request submitted. Admin will review shortly.',
                        'requested_at' => $capability->sell_requested_at,
                    ],
                    'message' => 'Sell capability request submitted successfully',
                ], 201);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid capability type. Must be "buy" or "sell".',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error("Failed to request capability: {$e->getMessage()}");

            return response()->json([
                'success' => false,
                'message' => 'Failed to process capability request',
            ], 500);
        }
    }
}
