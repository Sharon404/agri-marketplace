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

        if ($user->role !== 'admin' && $order->buyer_id !== $user->id) {
            return response()->json(['message' => 'Not authorized.'], 403);
        }

        $data = $request->validated();

        $order->update([
            'status' => $data['status'],
            'payment_status' => $data['payment_status'] ?? $order->payment_status,
        ]);

        return new OrderResource($order->load(['items.product', 'payment']));
    }
}
