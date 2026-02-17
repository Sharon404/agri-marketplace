<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\OrderStatusRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = Order::where('buyer_id', $request->user()->id)
            ->with(['items.product', 'payment'])
            ->latest()
            ->paginate(20);

        return OrderResource::collection($orders);
    }

    public function show(Request $request, Order $order)
    {
        if ($order->buyer_id !== $request->user()->id && $request->user()->role !== 'admin') {
            return response()->json(['message' => 'Not authorized.'], 403);
        }

        return new OrderResource($order->load(['items.product', 'payment']));
    }

    public function updateStatus(OrderStatusRequest $request, Order $order)
    {
        $user = $request->user();

        // Admin can update any order
        if ($user->role === 'admin') {
            $data = $request->validated();
            $order->update([
                'status' => $data['status'],
                'payment_status' => $data['payment_status'] ?? $order->payment_status,
            ]);
            return new OrderResource($order->load(['items.product', 'payment']));
        }

        // Buyer can only cancel their own order
        if ($user->role === 'buyer') {
            if ($order->buyer_id !== $user->id) {
                return response()->json(['message' => 'Not authorized.'], 403);
            }
            $data = $request->validated();
            if ($data['status'] !== 'cancelled') {
                return response()->json(['message' => 'Buyers can only cancel orders.'], 422);
            }
            $order->update([
                'status' => 'cancelled',
                'payment_status' => $data['payment_status'] ?? $order->payment_status,
            ]);
            return new OrderResource($order->load(['items.product', 'payment']));
        }

        // Seller can only transition items they own (ship, deliver, refund)
        if ($user->role === 'seller' && $user->sellerProfile) {
            $sellerOwnsItems = $order->items()
                ->where('seller_id', $user->sellerProfile->id)
                ->exists();

            if (!$sellerOwnsItems) {
                return response()->json(['message' => 'Seller does not own items in this order.'], 403);
            }

            $data = $request->validated();
            $allowedStatuses = ['shipped', 'delivered', 'refunded'];

            if (!in_array($data['status'], $allowedStatuses)) {
                return response()->json(['message' => 'Sellers can only set shipped, delivered, or refunded.'], 422);
            }

            $order->update([
                'status' => $data['status'],
                'payment_status' => $data['payment_status'] ?? $order->payment_status,
            ]);
            return new OrderResource($order->load(['items.product', 'payment']));
        }

        return response()->json(['message' => 'Not authorized.'], 403);
    }
}
