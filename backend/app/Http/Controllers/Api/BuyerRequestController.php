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
        $this->middleware('auth:api');
        $this->middleware('role:buyer')->only(['store', 'update', 'destroy']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = BuyerRequest::with(['buyer', 'product'])->active();

        // Filtering
        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->has('delivery_location')) {
            $query->where('delivery_location', 'like', '%' . $request->delivery_location . '%');
        }

        if ($request->has('urgency')) {
            $query->where('urgency', $request->urgency);
        }

        if ($request->has('max_price')) {
            $query->where('target_price', '<=', $request->max_price);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        return BuyerRequestResource::collection($query->paginate(20));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|numeric|min:0.01',
            'target_price' => 'nullable|numeric|min:0.01',
            'delivery_location' => 'required|string|max:255',
            'urgency' => 'required|in:low,medium,high',
            'description' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $buyerRequest = BuyerRequest::create([
            'buyer_id' => auth()->id(),
            'product_id' => $request->product_id,
            'quantity' => $request->quantity,
            'target_price' => $request->target_price,
            'delivery_location' => $request->delivery_location,
            'urgency' => $request->urgency,
            'description' => $request->description,
        ]);

        return new BuyerRequestResource($buyerRequest->load(['buyer', 'product']));
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
