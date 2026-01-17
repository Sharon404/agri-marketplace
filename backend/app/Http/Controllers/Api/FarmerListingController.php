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
        // Auth middleware disabled for mock mode
        // $this->middleware('auth:api');
        // $this->middleware('role:farmer')->only(['store', 'update', 'destroy']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Load listings from JSON file
        $listingsFile = base_path('storage/app/farmer_listings.json');
        $listingsData = [];
        if (file_exists($listingsFile)) {
            $jsonContent = file_get_contents($listingsFile);
            if ($jsonContent) {
                $listingsData = json_decode($jsonContent, true);
            }
        }

        return response()->json([
            'data' => $listingsData['listings'] ?? [],
            'meta' => [
                'total' => count($listingsData['listings'] ?? []),
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
            'product_id' => 'required|integer',
            'quantity' => 'required|numeric|min:0.01',
            'unit_price' => 'required|numeric|min:0.01',
            'location' => 'required|string|max:255',
            'availability_date' => 'required|date|after:today',
            'description' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Load listings from JSON file
        $listingsFile = base_path('storage/app/farmer_listings.json');
        $listingsData = [];
        if (file_exists($listingsFile)) {
            $jsonContent = file_get_contents($listingsFile);
            if ($jsonContent) {
                $listingsData = json_decode($jsonContent, true);
            }
        }

        // Get product name from products.json
        $productName = 'Product';
        $productsFile = base_path('storage/app/products.json');
        if (file_exists($productsFile)) {
            $jsonContent = file_get_contents($productsFile);
            if ($jsonContent) {
                $productsData = json_decode($jsonContent, true);
                foreach ($productsData['products'] ?? [] as $product) {
                    if ($product['id'] === (int)$request->product_id) {
                        $productName = $product['name'];
                        break;
                    }
                }
            }
        }

        // Create listing
        $newId = (count($listingsData['listings'] ?? []) + 1);
        $listing = [
            'id' => $newId,
            'farmer_id' => 1,
            'product_id' => (int)$request->product_id,
            'product' => ['id' => (int)$request->product_id, 'name' => $productName],
            'quantity' => (float)$request->quantity,
            'unit_price' => (float)$request->unit_price,
            'location' => $request->location,
            'availability_date' => $request->availability_date,
            'description' => $request->description,
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ];

        $listingsData['listings'][] = $listing;
        file_put_contents($listingsFile, json_encode($listingsData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return response()->json($listing, 201);
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
