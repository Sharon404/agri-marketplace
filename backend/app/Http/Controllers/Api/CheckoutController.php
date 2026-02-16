<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutRequest;
use App\Http\Resources\OrderResource;
use App\Services\CheckoutService;

class CheckoutController extends Controller
{
    public function store(CheckoutRequest $request, CheckoutService $checkoutService)
    {
        $order = $checkoutService->checkout($request->user(), $request->validated()['payment_method']);

        return new OrderResource($order->load(['items.product', 'payment']));
    }
}
