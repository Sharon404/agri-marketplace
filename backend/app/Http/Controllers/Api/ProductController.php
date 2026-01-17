<?php

namespace App\Http\Controllers\Api;

use Illuminate\Routing\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    public function __construct()
    {
        // Only require auth for write operations (store, update, destroy)
        $this->middleware('auth:api')->only(['store', 'update', 'destroy']);
        $this->middleware('role:admin')->only(['store', 'update', 'destroy']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Read products from JSON file
        $productsFile = base_path('storage/app/products.json');
        $products = [];
        
        if (file_exists($productsFile)) {
            $jsonContent = file_get_contents($productsFile);
            if ($jsonContent) {
                $data = json_decode($jsonContent, true);
                $products = $data['products'] ?? [];
            }
        }

        // Apply search filter if provided
        if ($request->has('search')) {
            $search = strtolower($request->search);
            $products = array_filter($products, function ($product) use ($search) {
                return stripos($product['name'], $search) !== false || 
                       stripos($product['category'], $search) !== false;
            });
        }

        // Apply category filter if provided
        if ($request->has('category')) {
            $category = $request->category;
            $products = array_filter($products, function ($product) use ($category) {
                return $product['category'] === $category;
            });
        }

        return response()->json([
            'data' => array_values($products),
            'meta' => [
                'total' => count($products),
                'per_page' => 20,
                'current_page' => 1,
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:products',
            'category' => 'required|string|max:255',
            'unit' => 'required|string|max:50',
            'description' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $product = Product::create($request->only(['name', 'category', 'unit', 'description']));

        return new ProductResource($product);
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        return new ProductResource($product);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255|unique:products,name,' . $product->id,
            'category' => 'sometimes|string|max:255',
            'unit' => 'sometimes|string|max:50',
            'description' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $product->update($request->only(['name', 'category', 'unit', 'description']));

        return new ProductResource($product);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        $product->delete();

        return response()->json(['message' => 'Product deleted successfully']);
    }
}
