<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BuyerRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'buyer' => [
                'id' => $this->buyer->id,
                'name' => $this->buyer->name,
            ],
            'product' => [
                'id' => $this->product->id,
                'name' => $this->product->name,
                'category' => $this->product->category,
                'unit' => $this->product->unit,
            ],
            'quantity' => $this->quantity,
            'target_price' => $this->target_price,
            'delivery_location' => $this->delivery_location,
            'urgency' => $this->urgency,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
