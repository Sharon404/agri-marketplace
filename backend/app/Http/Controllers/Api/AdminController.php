<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SellerProfileResource;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    /**
     * Get all pending seller verifications
     */
    public function pendingSellers(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $sellers = SellerProfile::where('verification_status', 'pending')
            ->with('user')
            ->paginate(15);

        return SellerProfileResource::collection($sellers);
    }

    /**
     * Get all sellers (verified and pending)
     */
    public function allSellers(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $sellers = SellerProfile::with('user')
            ->paginate(15);

        return SellerProfileResource::collection($sellers);
    }

    /**
     * Approve a seller for verification
     */
    public function verifySeller(Request $request, SellerProfile $seller)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $seller->update([
            'verification_status' => 'verified',
            'verified_at' => now(),
            'rejection_reason' => null,
        ]);

        return response()->json([
            'message' => 'Seller verified successfully',
            'seller' => new SellerProfileResource($seller->load('user')),
        ]);
    }

    /**
     * Reject a seller verification
     */
    public function rejectSeller(Request $request, SellerProfile $seller)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $seller->update([
            'verification_status' => 'rejected',
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        return response()->json([
            'message' => 'Seller rejected',
            'seller' => new SellerProfileResource($seller->load('user')),
        ]);
    }
}
