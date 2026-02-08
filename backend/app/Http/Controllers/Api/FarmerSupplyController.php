<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FarmerSupply;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FarmerSupplyController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
        $this->middleware('role:farmer');
        $this->middleware('require.email.verified');
    }

    /**
     * Get all supplies for authenticated farmer
     */
    public function index(Request $request)
    {
        $farmer = auth()->user();
        $query = FarmerSupply::where('farmer_id', $farmer->id)
            ->with(['product']);

        if ($request->product_id) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->status === 'active') {
            $query->where('is_active', true);
        } elseif ($request->status === 'inactive') {
            $query->where('is_active', false);
        }

        $supplies = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json($supplies);
    }

    /**
     * Create a new supply
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|integer|exists:products,id',
            'quantity_available' => 'required|numeric|min:0.01',
            'unit' => 'required|string|in:kg,tons,bags,liters,pieces',
            'price_per_unit' => 'required|numeric|min:0',
            'available_from' => 'required|date|after_or_equal:today',
            'available_until' => 'required|date|after:available_from',
            'description' => 'nullable|string|max:1000',
            'delivery_terms' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $farmer = auth()->user();

        $supply = FarmerSupply::create([
            'farmer_id' => $farmer->id,
            'product_id' => $request->product_id,
            'quantity_available' => $request->quantity_available,
            'unit' => $request->unit,
            'price_per_unit' => $request->price_per_unit,
            'available_from' => $request->available_from,
            'available_until' => $request->available_until,
            'description' => $request->description,
            'delivery_terms' => $request->delivery_terms,
            'is_active' => true,
        ]);

        return response()->json([
            'message' => 'Supply created successfully',
            'data' => $supply->load(['product']),
        ], 201);
    }

    /**
     * Get a specific supply
     */
    public function show($id)
    {
        $farmer = auth()->user();
        $supply = FarmerSupply::where('farmer_id', $farmer->id)
            ->with(['product', 'deals'])
            ->findOrFail($id);

        return response()->json($supply);
    }

    /**
     * Update a supply
     */
    public function update(Request $request, $id)
    {
        $farmer = auth()->user();
        $supply = FarmerSupply::where('farmer_id', $farmer->id)->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'quantity_available' => 'sometimes|numeric|min:0.01',
            'price_per_unit' => 'sometimes|numeric|min:0',
            'available_until' => 'sometimes|date|after:available_from',
            'description' => 'nullable|string|max:1000',
            'delivery_terms' => 'nullable|string|max:500',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $supply->update($request->only([
            'quantity_available',
            'price_per_unit',
            'available_until',
            'description',
            'delivery_terms',
            'is_active',
        ]));

        return response()->json([
            'message' => 'Supply updated successfully',
            'data' => $supply,
        ]);
    }

    /**
     * Delete a supply
     */
    public function destroy($id)
    {
        $farmer = auth()->user();
        $supply = FarmerSupply::where('farmer_id', $farmer->id)->findOrFail($id);

        // Don't delete if there are active deals
        if ($supply->deals()->whereIn('status', ['accepted', 'in_transit', 'delivered'])->exists()) {
            return response()->json([
                'error' => 'Cannot delete supply with active deals',
            ], 422);
        }

        $supply->delete();

        return response()->json([
            'message' => 'Supply deleted successfully',
        ]);
    }

    /**
     * Get available supplies (public view for buyers)
     */
    public function listAvailable(Request $request)
    {
        $query = FarmerSupply::active()->with(['farmer', 'product']);

        if ($request->product_id) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->min_price) {
            $query->where('price_per_unit', '>=', $request->min_price);
        }

        if ($request->max_price) {
            $query->where('price_per_unit', '<=', $request->max_price);
        }

        if ($request->sort_by === 'price_low') {
            $query->orderBy('price_per_unit', 'asc');
        } elseif ($request->sort_by === 'price_high') {
            $query->orderBy('price_per_unit', 'desc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $supplies = $query->paginate(15);

        return response()->json($supplies);
    }
}
