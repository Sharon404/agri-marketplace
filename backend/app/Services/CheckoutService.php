<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\SellerPayout;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    public function checkout(User $buyer, string $paymentMethod): Order
    {
        return DB::transaction(function () use ($buyer, $paymentMethod) {
            $cart = Cart::where('user_id', $buyer->id)
                ->with(['items.product.shipping', 'items.product.sellerProfile'])
                ->lockForUpdate()
                ->first();

            if (!$cart || $cart->items->isEmpty()) {
                throw ValidationException::withMessages([
                    'cart' => 'Cart is empty.',
                ]);
            }

            $totals = $this->calculateTotals($cart);

            $order = Order::create([
                'buyer_id' => $buyer->id,
                'total_amount' => $totals['total_amount'],
                'shipping_amount' => $totals['shipping_amount'],
                'commission_amount' => $totals['commission_amount'],
                'status' => 'pending',
                'payment_status' => 'unpaid',
            ]);

            foreach ($totals['items'] as $itemData) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $itemData['product_id'],
                    'seller_id' => $itemData['seller_id'],
                    'quantity' => $itemData['quantity'],
                    'price_per_unit' => $itemData['price_per_unit'],
                    'subtotal' => $itemData['subtotal'],
                    'shipping_fee' => $itemData['shipping_fee'],
                ]);

                $itemData['product']->decrement('stock_quantity', $itemData['quantity']);
            }

            foreach ($totals['payouts'] as $payoutData) {
                SellerPayout::create([
                    'seller_id' => $payoutData['seller_id'],
                    'order_id' => $order->id,
                    'gross_amount' => $payoutData['gross_amount'],
                    'commission_amount' => $payoutData['commission_amount'],
                    'net_amount' => $payoutData['net_amount'],
                    'status' => 'pending',
                ]);
            }

            Payment::create([
                'order_id' => $order->id,
                'payment_method' => $paymentMethod,
                'transaction_reference' => null,
                'amount' => $totals['total_amount'],
                'status' => 'pending',
            ]);

            $cart->items()->delete();

            return $order->load(['items.product', 'payment']);
        });
    }

    private function calculateTotals(Cart $cart): array
    {
        $items = [];
        $payouts = [];
        $totalAmount = 0.0;
        $shippingAmount = 0.0;
        $commissionAmount = 0.0;

        foreach ($cart->items as $cartItem) {
            $product = $cartItem->product;

            if (!$product || !$product->is_active) {
                throw ValidationException::withMessages([
                    'product' => 'One or more products are unavailable.',
                ]);
            }

            if ($product->stock_quantity < $cartItem->quantity) {
                throw ValidationException::withMessages([
                    'stock' => 'Insufficient stock for ' . $product->name . '.',
                ]);
            }

            if ($cartItem->quantity < $product->minimum_order_quantity) {
                throw ValidationException::withMessages([
                    'minimum_order_quantity' => 'Minimum order quantity not met for ' . $product->name . '.',
                ]);
            }

            $subtotal = (float) $product->price * $cartItem->quantity;
            $shippingFee = $this->calculateShippingFee($product, $subtotal);

            $commissionRate = $product->sellerProfile && $product->sellerProfile->commission_rate !== null
                ? (float) $product->sellerProfile->commission_rate / 100
                : (float) config('marketplace.default_commission');

            $commissionForItem = $subtotal * $commissionRate;

            $items[] = [
                'product_id' => $product->id,
                'seller_id' => $product->seller_id,
                'quantity' => $cartItem->quantity,
                'price_per_unit' => $product->price,
                'subtotal' => $subtotal,
                'shipping_fee' => $shippingFee,
                'product' => $product,
            ];

            if (!isset($payouts[$product->seller_id])) {
                $payouts[$product->seller_id] = [
                    'seller_id' => $product->seller_id,
                    'gross_amount' => 0.0,
                    'commission_amount' => 0.0,
                    'net_amount' => 0.0,
                ];
            }

            $payouts[$product->seller_id]['gross_amount'] += $subtotal + $shippingFee;
            $payouts[$product->seller_id]['commission_amount'] += $commissionForItem;

            $totalAmount += $subtotal + $shippingFee;
            $shippingAmount += $shippingFee;
            $commissionAmount += $commissionForItem;
        }

        foreach ($payouts as $sellerId => $payout) {
            $payouts[$sellerId]['net_amount'] = $payout['gross_amount'] - $payout['commission_amount'];
        }

        return [
            'items' => $items,
            'payouts' => array_values($payouts),
            'total_amount' => $totalAmount,
            'shipping_amount' => $shippingAmount,
            'commission_amount' => $commissionAmount,
        ];
    }

    private function calculateShippingFee($product, float $subtotal): float
    {
        $shipping = $product->shipping;

        if (!$shipping) {
            return 0.0;
        }

        if ($shipping->shipping_type === 'free') {
            return 0.0;
        }

        if ($shipping->free_shipping_minimum !== null && $subtotal >= (float) $shipping->free_shipping_minimum) {
            return 0.0;
        }

        return $shipping->flat_shipping_fee !== null ? (float) $shipping->flat_shipping_fee : 0.0;
    }
}
