<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductShippingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'shipping_type' => $this->shipping_type,
            'flat_shipping_fee' => $this->flat_shipping_fee,
            'free_shipping_minimum' => $this->free_shipping_minimum,
        ];
    }
}
