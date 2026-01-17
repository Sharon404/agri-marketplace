<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\FarmerListingResource;
use App\Models\FarmerListing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FarmerListingController extends Controller
{
    public function __construct()
    {
        // Auth middleware disabled for mock mode - will be re-enabled when JWT is ready
        // $this->middleware('auth:api');
        // $this->middleware('role:farmer')->only(['store', 'update', 'destroy']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Get listings from database with product relation
        $listings = FarmerListing::with('product')->where('is_active', true)->get();

        return response()->json([
            'data' => $listings,
            'meta' => [
                'total' => count($listings),
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
            'unit_price' => 'required|numeric|min:0.01',
            'location' => 'required|string|max:255',
            'availability_date' => 'required|date|after:today',
            'description' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Extract user ID from JWT token in Authorization header
        $farmerId = $this->getUserIdFromToken($request);
        if (!$farmerId) {
            // Fallback to farmer_id from request if provided
            $farmerId = $request->input('farmer_id', 1);
        }

        // Create listing in database
        $listing = FarmerListing::create([
            'farmer_id' => $farmerId,
            'product_id' => $request->product_id,
            'quantity' => $request->quantity,
            'unit_price' => $request->unit_price,
            'location' => $request->location,
            'availability_date' => $request->availability_date,
            'description' => $request->description,
            'is_active' => true,
        ]);

        return response()->json($listing->load('product'), 201);
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
    public function show(FarmerListing $farmerListing)
    {
        return new FarmerListingResource($farmerListing->load(['farmer', 'product']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, FarmerListing $farmerListing)
    {
        // Ensure user owns the listing
        if ($farmerListing->farmer_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'quantity' => 'sometimes|numeric|min:0.01',
            'unit_price' => 'sometimes|numeric|min:0.01',
            'location' => 'sometimes|string|max:255',
            'availability_date' => 'sometimes|date|after:today',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $farmerListing->update($request->only([
            'quantity', 'unit_price', 'location', 'availability_date', 'description', 'is_active'
        ]));

        return new FarmerListingResource($farmerListing->load(['farmer', 'product']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FarmerListing $farmerListing)
    {
        // Ensure user owns the listing
        if ($farmerListing->farmer_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $farmerListing->delete();

        return response()->json(['message' => 'Farmer listing deleted successfully']);
    }
}
