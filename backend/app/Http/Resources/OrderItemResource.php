<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\ProductResource;

class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'product_id' => $this->product_id,
            'seller_id' => $this->seller_id,
            'quantity' => $this->quantity,
            'price_per_unit' => $this->price_per_unit,
            'subtotal' => $this->subtotal,
            'shipping_fee' => $this->shipping_fee,
            'product' => new ProductResource($this->whenLoaded('product')),
        ];
    }
}
