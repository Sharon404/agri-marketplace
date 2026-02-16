<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductImageResource;
use App\Http\Resources\ProductShippingResource;
use App\Http\Resources\SellerProfileResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'seller_id' => $this->seller_id,
            'category_id' => $this->category_id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'minimum_order_quantity' => $this->minimum_order_quantity,
            'stock_quantity' => $this->stock_quantity,
            'weight_per_unit' => $this->weight_per_unit,
            'is_active' => $this->is_active,
            'seller_profile' => new SellerProfileResource($this->whenLoaded('sellerProfile')),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'images' => ProductImageResource::collection($this->whenLoaded('images')),
            'shipping' => new ProductShippingResource($this->whenLoaded('shipping')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
