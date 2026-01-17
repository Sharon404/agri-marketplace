<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BuyerRequestResource;
use App\Models\BuyerRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BuyerRequestController extends Controller
{
    public function __construct()
    {
        // Auth middleware disabled for mock mode
        // $this->middleware('auth:api');
        // $this->middleware('role:buyer')->only(['store', 'update', 'destroy']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Load requests from JSON file
        $requestsFile = base_path('storage/app/buyer_requests.json');
        $requestsData = [];
        if (file_exists($requestsFile)) {
            $jsonContent = file_get_contents($requestsFile);
            if ($jsonContent) {
                $requestsData = json_decode($jsonContent, true);
            }
        }

        return response()->json([
            'data' => $requestsData['requests'] ?? [],
            'meta' => [
                'total' => count($requestsData['requests'] ?? []),
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
            'target_price' => 'nullable|numeric|min:0.01',
            'delivery_location' => 'required|string|max:255',
            'urgency' => 'required|in:low,medium,high',
            'description' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Load requests from JSON file
        $requestsFile = base_path('storage/app/buyer_requests.json');
        $requestsData = [];
        if (file_exists($requestsFile)) {
            $jsonContent = file_get_contents($requestsFile);
            if ($jsonContent) {
                $requestsData = json_decode($jsonContent, true);
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

        // Create request
        $newId = (count($requestsData['requests'] ?? []) + 1);
        $buyerRequest = [
            'id' => $newId,
            'buyer_id' => 2,
            'product_id' => (int)$request->product_id,
            'product' => ['id' => (int)$request->product_id, 'name' => $productName],
            'quantity' => (float)$request->quantity,
            'target_price' => $request->target_price ? (float)$request->target_price : null,
            'delivery_location' => $request->delivery_location,
            'urgency' => $request->urgency,
            'description' => $request->description,
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ];

        $requestsData['requests'][] = $buyerRequest;
        file_put_contents($requestsFile, json_encode($requestsData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return response()->json($buyerRequest, 201);
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
