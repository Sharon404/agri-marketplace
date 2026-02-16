<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddToCartRequest;
use App\Http\Requests\UpdateCartItemRequest;
use App\Http\Resources\CartResource;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function show(Request $request)
    {
        $cart = Cart::firstOrCreate(['user_id' => $request->user()->id]);
        $cart->load(['items.product.images', 'items.product.shipping']);

        return new CartResource($cart);
    }

    public function add(AddToCartRequest $request)
    {
        $data = $request->validated();
        $cart = Cart::firstOrCreate(['user_id' => $request->user()->id]);

        $product = Product::findOrFail($data['product_id']);

        if (!$product->is_active) {
            return response()->json(['message' => 'Product not available.'], 422);
        }

        $item = CartItem::firstOrCreate(
            ['cart_id' => $cart->id, 'product_id' => $product->id],
            ['quantity' => 0]
        );

        $item->quantity += $data['quantity'];
        $item->save();

        $cart->load(['items.product.images', 'items.product.shipping']);

        return new CartResource($cart);
    }

    public function update(UpdateCartItemRequest $request, CartItem $cartItem)
    {
        $cart = Cart::where('user_id', $request->user()->id)->firstOrFail();

        if ($cartItem->cart_id !== $cart->id) {
            return response()->json(['message' => 'Not authorized.'], 403);
        }

        $cartItem->update($request->validated());

        $cart->load(['items.product.images', 'items.product.shipping']);

        return new CartResource($cart);
    }

    public function remove(Request $request, CartItem $cartItem)
    {
        $cart = Cart::where('user_id', $request->user()->id)->firstOrFail();

        if ($cartItem->cart_id !== $cart->id) {
            return response()->json(['message' => 'Not authorized.'], 403);
        }

        $cartItem->delete();

        $cart->load(['items.product.images', 'items.product.shipping']);

        return new CartResource($cart);
    }

    public function clear(Request $request)
    {
        $cart = Cart::where('user_id', $request->user()->id)->firstOrFail();
        $cart->items()->delete();

        $cart->load(['items.product.images', 'items.product.shipping']);

        return new CartResource($cart);
    }
}
