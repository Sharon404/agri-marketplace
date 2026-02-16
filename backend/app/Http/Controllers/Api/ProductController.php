<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductShipping;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query()->with(['sellerProfile', 'category', 'images', 'shipping']);

        if ($request->has('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        $products = $query->where('is_active', true)->latest()->paginate(20);

        return ProductResource::collection($products);
    }

    public function show(Product $product)
    {
        $product->load(['sellerProfile', 'category', 'images', 'shipping']);

        return new ProductResource($product);
    }

    public function store(ProductRequest $request)
    {
        $user = $request->user();

        if ($user->role !== 'seller' || !$user->sellerProfile) {
            return response()->json(['message' => 'Seller profile required.'], 403);
        }

        $data = $request->validated();

        $product = Product::create([
            'seller_id' => $user->sellerProfile->id,
            'category_id' => $data['category_id'],
            'name' => $data['name'],
            'description' => $data['description'],
            'price' => $data['price'],
            'minimum_order_quantity' => $data['minimum_order_quantity'] ?? 1,
            'stock_quantity' => $data['stock_quantity'],
            'weight_per_unit' => $data['weight_per_unit'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);

        if (!empty($data['images'])) {
            foreach ($data['images'] as $image) {
                ProductImage::create([
                    'product_id' => $product->id,
                    'image_url' => $image['image_url'],
                    'is_primary' => $image['is_primary'] ?? false,
                ]);
            }
        }

        if (!empty($data['shipping'])) {
            ProductShipping::updateOrCreate(
                ['product_id' => $product->id],
                [
                    'shipping_type' => $data['shipping']['shipping_type'],
                    'flat_shipping_fee' => $data['shipping']['flat_shipping_fee'] ?? null,
                    'free_shipping_minimum' => $data['shipping']['free_shipping_minimum'] ?? null,
                ]
            );
        }

        return new ProductResource($product->load(['sellerProfile', 'category', 'images', 'shipping']));
    }

    public function update(ProductRequest $request, Product $product)
    {
        $user = $request->user();

        if ($user->role !== 'seller' || !$user->sellerProfile || $product->seller_id !== $user->sellerProfile->id) {
            return response()->json(['message' => 'Not authorized.'], 403);
        }

        $data = $request->validated();

        $product->update([
            'category_id' => $data['category_id'],
            'name' => $data['name'],
            'description' => $data['description'],
            'price' => $data['price'],
            'minimum_order_quantity' => $data['minimum_order_quantity'] ?? $product->minimum_order_quantity,
            'stock_quantity' => $data['stock_quantity'],
            'weight_per_unit' => $data['weight_per_unit'] ?? $product->weight_per_unit,
            'is_active' => $data['is_active'] ?? $product->is_active,
        ]);

        if (array_key_exists('images', $data)) {
            $product->images()->delete();
            foreach ($data['images'] ?? [] as $image) {
                ProductImage::create([
                    'product_id' => $product->id,
                    'image_url' => $image['image_url'],
                    'is_primary' => $image['is_primary'] ?? false,
                ]);
            }
        }

        if (array_key_exists('shipping', $data)) {
            if ($data['shipping'] === null) {
                $product->shipping()->delete();
            } else {
                ProductShipping::updateOrCreate(
                    ['product_id' => $product->id],
                    [
                        'shipping_type' => $data['shipping']['shipping_type'],
                        'flat_shipping_fee' => $data['shipping']['flat_shipping_fee'] ?? null,
                        'free_shipping_minimum' => $data['shipping']['free_shipping_minimum'] ?? null,
                    ]
                );
            }
        }

        return new ProductResource($product->load(['sellerProfile', 'category', 'images', 'shipping']));
    }

    public function destroy(Request $request, Product $product)
    {
        $user = $request->user();

        if ($user->role !== 'seller' || !$user->sellerProfile || $product->seller_id !== $user->sellerProfile->id) {
            return response()->json(['message' => 'Not authorized.'], 403);
        }

        $product->delete();

        return response()->json(['message' => 'Product deleted']);
    }
}
