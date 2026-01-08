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
        $this->middleware('auth:api');
        $this->middleware('role:farmer')->only(['store', 'update', 'destroy']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = FarmerListing::with(['farmer', 'product'])->active();

        // Filtering
        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->has('location')) {
            $query->where('location', 'like', '%' . $request->location . '%');
        }

        if ($request->has('min_price')) {
            $query->where('unit_price', '>=', $request->min_price);
        }

        if ($request->has('max_price')) {
            $query->where('unit_price', '<=', $request->max_price);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        return FarmerListingResource::collection($query->paginate(20));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|numeric|min:0.01',
            'unit_price' => 'required|numeric|min:0.01',
            'location' => 'required|string|max:255',
            'availability_date' => 'required|date|after:today',
            'description' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $listing = FarmerListing::create([
            'farmer_id' => auth()->id(),
            'product_id' => $request->product_id,
            'quantity' => $request->quantity,
            'unit_price' => $request->unit_price,
            'location' => $request->location,
            'availability_date' => $request->availability_date,
            'description' => $request->description,
        ]);

        return new FarmerListingResource($listing->load(['farmer', 'product']));
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
