<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Deal;
use App\Models\BuyerRequest;
use App\Models\FarmerSupply;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class DealController extends Controller
{
    use AuthorizesRequests;

    public function __construct()
    {
        // Only admin can access these endpoints
        $this->middleware('auth:api');
        $this->middleware('role:admin');
    }

    /**
     * List all buyer requests for admin to review
     */
    public function listBuyerRequests(Request $request)
    {
        $query = BuyerRequest::with(['buyer', 'product'])
            ->where('status', 'active');

        if ($request->product_id) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->sort_by === 'recent') {
            $query->orderBy('created_at', 'desc');
        } else {
            $query->orderBy('quantity', 'desc');
        }

        $requests = $query->paginate(15);

        return response()->json($requests);
    }

    /**
     * List all farmer supplies for admin to review
     */
    public function listFarmerSupplies(Request $request)
    {
        $query = FarmerSupply::with(['farmer', 'product'])
            ->where('is_active', true)
            ->where('available_from', '<=', now()->toDateString())
            ->where('available_until', '>=', now()->toDateString());

        if ($request->product_id) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->sort_by === 'recent') {
            $query->orderBy('created_at', 'desc');
        } else {
            $query->orderBy('quantity_available', 'desc');
        }

        $supplies = $query->paginate(15);

        return response()->json($supplies);
    }

    /**
     * Create a deal (ADMIN ONLY)
     *
     * POST /admin/deals
     * {
     *   "buyer_id": 2,
     *   "farmer_id": 1,
     *   "product_id": 1,
     *   "buyer_request_id": 1 (optional),
     *   "farmer_supply_id": 1 (optional),
     *   "quantity": 100,
     *   "agreed_price": 50,
     *   "delivery_location": "Nairobi",
     *   "delivery_date": "2026-02-15",
     *   "admin_notes": "Matched based on quality grade A"
     * }
     */
    public function createDeal(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'buyer_id' => 'required|integer|exists:users,id',
            'farmer_id' => 'required|integer|exists:users,id',
            'product_id' => 'required|integer|exists:products,id',
            'buyer_request_id' => 'nullable|integer|exists:buyer_requests,id',
            'farmer_supply_id' => 'nullable|integer|exists:farmer_supplies,id',
            'quantity' => 'required|numeric|min:0.01',
            'agreed_price' => 'required|numeric|min:0',
            'delivery_location' => 'required|string|max:255',
            'delivery_date' => 'required|date|after:today',
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Verify buyer and farmer roles
        $buyer = \App\Models\User::find($request->buyer_id);
        $farmer = \App\Models\User::find($request->farmer_id);

        if ($buyer->role !== 'buyer') {
            return response()->json(['error' => 'Selected user is not a buyer'], 422);
        }

        if ($farmer->role !== 'farmer') {
            return response()->json(['error' => 'Selected user is not a farmer'], 422);
        }

        // Verify farmer supply has enough quantity
        if ($request->farmer_supply_id) {
            $supply = FarmerSupply::find($request->farmer_supply_id);
            if ($supply->quantity_available < $request->quantity) {
                return response()->json([
                    'error' => 'Insufficient quantity available',
                    'available' => $supply->quantity_available,
                    'requested' => $request->quantity,
                ], 422);
            }
        }

        $totalAmount = $request->quantity * $request->agreed_price;

        $deal = Deal::create([
            'buyer_id' => $request->buyer_id,
            'farmer_id' => $request->farmer_id,
            'product_id' => $request->product_id,
            'buyer_request_id' => $request->buyer_request_id,
            'farmer_supply_id' => $request->farmer_supply_id,
            'quantity' => $request->quantity,
            'agreed_price' => $request->agreed_price,
            'total_amount' => $totalAmount,
            'delivery_location' => $request->delivery_location,
            'delivery_date' => $request->delivery_date,
            'status' => 'pending_buyer_confirmation',
            'payment_status' => 'unpaid',
            'admin_notes' => $request->admin_notes,
            'created_by_admin' => true,
        ]);

        // Notify buyer
        Notification::create([
            'user_id' => $request->buyer_id,
            'type' => 'deal_created',
            'title' => 'New Deal Available',
            'message' => "Admin has created a deal for {$request->quantity} units of product at {$request->agreed_price}/unit",
            'priority' => 'high',
            'action_url' => "/deals/{$deal->id}",
        ]);

        // Notify farmer
        Notification::create([
            'user_id' => $request->farmer_id,
            'type' => 'deal_created',
            'title' => 'New Deal Request',
            'message' => "Admin has created a deal for {$request->quantity} units at {$request->agreed_price}/unit",
            'priority' => 'high',
            'action_url' => "/deals/{$deal->id}",
        ]);

        return response()->json([
            'message' => 'Deal created successfully',
            'data' => $deal->load(['buyer', 'farmer', 'product']),
        ], 201);
    }

    /**
     * Update deal (ADMIN ONLY)
     */
    public function updateDeal(Request $request, $id)
    {
        $deal = Deal::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'delivery_date' => 'nullable|date',
            'delivery_location' => 'nullable|string|max:255',
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $deal->update($request->only(['delivery_date', 'delivery_location', 'admin_notes']));

        return response()->json([
            'message' => 'Deal updated successfully',
            'data' => $deal,
        ]);
    }

    /**
     * List all deals (admin view)
     */
    public function listDeals(Request $request)
    {
        $query = Deal::with(['buyer', 'farmer', 'product']);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->buyer_id) {
            $query->where('buyer_id', $request->buyer_id);
        }

        if ($request->farmer_id) {
            $query->where('farmer_id', $request->farmer_id);
        }

        $deals = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json($deals);
    }

    /**
     * Get deal details
     */
    public function showDeal($id)
    {
        $deal = Deal::with(['buyer', 'farmer', 'product', 'payment'])->findOrFail($id);

        return response()->json($deal);
    }

    /**
     * Cancel deal (ADMIN ONLY)
     */
    public function cancelDeal(Request $request, $id)
    {
        $deal = Deal::findOrFail($id);

        if ($deal->status === 'completed') {
            return response()->json(['error' => 'Cannot cancel completed deal'], 422);
        }

        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // If payment was made, refund it
        $payment = $deal->payment();
        if ($payment && in_array($payment->status, ['pending', 'escrowed'])) {
            $payment->refund();
        }

        $deal->update([
            'status' => 'cancelled',
            'admin_notes' => $deal->admin_notes . "\n\nCancelled by admin: " . $request->reason,
        ]);

        // Notify both parties
        Notification::create([
            'user_id' => $deal->buyer_id,
            'type' => 'deal_cancelled',
            'title' => 'Deal Cancelled',
            'message' => 'Admin has cancelled the deal. Reason: ' . $request->reason,
            'priority' => 'high',
        ]);

        Notification::create([
            'user_id' => $deal->farmer_id,
            'type' => 'deal_cancelled',
            'title' => 'Deal Cancelled',
            'message' => 'Admin has cancelled the deal. Reason: ' . $request->reason,
            'priority' => 'high',
        ]);

        return response()->json([
            'message' => 'Deal cancelled successfully',
            'data' => $deal,
        ]);
    }

    /**
     * Release escrow (ADMIN ONLY)
     */
    public function releaseEscrow(Request $request, $id)
    {
        $deal = Deal::findOrFail($id);
        $payment = $deal->payment();

        if (!$payment) {
            return response()->json(['error' => 'No payment found for this deal'], 404);
        }

        if ($payment->status !== 'escrowed') {
            return response()->json(['error' => 'Payment is not in escrowed state'], 422);
        }

        $validator = Validator::make($request->all(), [
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $payment->releaseEscrow();

        // Update deal status
        $deal->update(['payment_status' => 'paid']);

        // Notify farmer payment released
        Notification::create([
            'user_id' => $deal->farmer_id,
            'type' => 'payment_released',
            'title' => 'Payment Released',
            'message' => 'Your payment of ' . $payment->amount . ' has been released from escrow',
            'priority' => 'high',
        ]);

        return response()->json([
            'message' => 'Escrow released successfully',
            'data' => $payment,
        ]);
    }
}
