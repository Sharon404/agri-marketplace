<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BuyerRequestResource;
use App\Models\BuyerRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BuyerRequestController extends Controller
{
    public function __construct()
    {
        // Protect write operations with authentication
        $this->middleware('auth:api')->only(['store', 'update', 'destroy']);
        
        // Capability-based access control (with role-based fallback)
        $this->middleware('capability:buy')->only(['store', 'update', 'destroy']);
        
        // OLD: Role-based access control (deprecated, kept for reference)
        // $this->middleware('role:buyer')->only(['store', 'update', 'destroy']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Get requests from database with product relation
        $requests = BuyerRequest::with('product')->where('is_active', true)->get();

        return response()->json([
            'data' => $requests,
            'meta' => [
                'total' => count($requests),
                'per_page' => 20,
                'current_page' => 1,
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|integer|exists:products,id',
            'quantity' => 'required|numeric|min:0.01',
            'target_price' => 'nullable|numeric|min:0.01',
            'delivery_location' => 'nullable|string|max:255', // Optional - can use buyer's county/sub_county
            'urgency' => 'required|in:low,medium,high',
            'description' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Use authenticated user ID (JWT guard)
        $buyerId = auth('api')->id();
        if (!$buyerId) {
            // Fallback to legacy token parsing or request input
            $buyerId = $this->getUserIdFromToken($request) ?: $request->input('buyer_id', 2);
        }

        // Get buyer's location if not provided
        $buyer = User::find($buyerId);
        $deliveryLocation = $request->delivery_location ?? ($buyer?->county ? $buyer->county . ', ' . ($buyer->sub_county ?? '') : 'TBD');

        // Create request in database
        $buyerRequest = BuyerRequest::create([
            'buyer_id' => $buyerId,
            'product_id' => $request->product_id,
            'quantity' => $request->quantity,
            'target_price' => $request->target_price,
            'delivery_location' => $deliveryLocation,
            'urgency' => $request->urgency,
            'description' => $request->description,
            'is_active' => true,
        ]);

        return response()->json($buyerRequest->load('product'), 201);
    }

    /**
     * Extract user ID from custom JWT token
     */
    private function getUserIdFromToken(Request $request)
    {
        $authHeader = $request->header('Authorization');
        if (!$authHeader) {
            return null;
        }

        // Format: "Bearer jwt_<base64_encoded_data>"
        if (preg_match('/Bearer\s+jwt_(.+)$/', $authHeader, $matches)) {
            $tokenData = base64_decode($matches[1]);
            // Format: "user_id:email:timestamp"
            $parts = explode(':', $tokenData);
            return intval($parts[0]) ?? null;
        }

        return null;
    }

    /**
     * Display the specified resource.
     */
    public function show(BuyerRequest $buyerRequest)
    {
        return new BuyerRequestResource($buyerRequest->load(['buyer', 'product']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, BuyerRequest $buyerRequest)
    {
        // Ensure user owns the request
        if ($buyerRequest->buyer_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'quantity' => 'sometimes|numeric|min:0.01',
            'target_price' => 'nullable|numeric|min:0.01',
            'delivery_location' => 'sometimes|string|max:255',
            'urgency' => 'sometimes|in:low,medium,high',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $buyerRequest->update($request->only([
            'quantity', 'target_price', 'delivery_location', 'urgency', 'description', 'is_active'
        ]));

        return new BuyerRequestResource($buyerRequest->load(['buyer', 'product']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BuyerRequest $buyerRequest)
    {
        // Ensure user owns the request
        if ($buyerRequest->buyer_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $buyerRequest->delete();

        return response()->json(['message' => 'Buyer request deleted successfully']);
    }
}
