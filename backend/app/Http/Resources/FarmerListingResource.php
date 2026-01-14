<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FarmerListingResource extends JsonResource
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
            'farmer' => [
                'id' => $this->farmer->id,
                'name' => $this->farmer->name,
            ],
            'product' => [
                'id' => $this->product->id,
                'name' => $this->product->name,
                'category' => $this->product->category,
                'unit' => $this->product->unit,
            ],
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'total_value' => $this->quantity * $this->unit_price,
            'location' => $this->location,
            'availability_date' => $this->availability_date,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
