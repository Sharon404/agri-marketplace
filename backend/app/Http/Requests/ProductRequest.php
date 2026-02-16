<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'minimum_order_quantity' => ['nullable', 'integer', 'min:1'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'weight_per_unit' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'images' => ['nullable', 'array'],
            'images.*.image_url' => ['required_with:images', 'url', 'max:2048'],
            'images.*.is_primary' => ['nullable', 'boolean'],
            'shipping' => ['nullable', 'array'],
            'shipping.shipping_type' => ['required_with:shipping', 'in:flat,free'],
            'shipping.flat_shipping_fee' => ['nullable', 'numeric', 'min:0'],
            'shipping.free_shipping_minimum' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
