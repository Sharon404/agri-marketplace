<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            // Grains
            ['name' => 'Rice', 'category' => 'Grains', 'unit' => 'kg', 'description' => 'High-quality rice varieties'],
            ['name' => 'Wheat', 'category' => 'Grains', 'unit' => 'kg', 'description' => 'Premium wheat grains'],
            ['name' => 'Maize', 'category' => 'Grains', 'unit' => 'kg', 'description' => 'Fresh maize/corn'],
            ['name' => 'Barley', 'category' => 'Grains', 'unit' => 'kg', 'description' => 'Nutritious barley grains'],
            ['name' => 'Oats', 'category' => 'Grains', 'unit' => 'kg', 'description' => 'Healthy oat grains'],

            // Vegetables
            ['name' => 'Tomatoes', 'category' => 'Vegetables', 'unit' => 'kg', 'description' => 'Fresh red tomatoes'],
            ['name' => 'Potatoes', 'category' => 'Vegetables', 'unit' => 'kg', 'description' => 'Starchy potatoes'],
            ['name' => 'Onions', 'category' => 'Vegetables', 'unit' => 'kg', 'description' => 'Red and white onions'],
            ['name' => 'Carrots', 'category' => 'Vegetables', 'unit' => 'kg', 'description' => 'Crunchy carrots'],
            ['name' => 'Cabbage', 'category' => 'Vegetables', 'unit' => 'kg', 'description' => 'Green cabbage heads'],
            ['name' => 'Spinach', 'category' => 'Vegetables', 'unit' => 'kg', 'description' => 'Leafy green spinach'],

            // Fruits
            ['name' => 'Bananas', 'category' => 'Fruits', 'unit' => 'kg', 'description' => 'Sweet bananas'],
            ['name' => 'Mangoes', 'category' => 'Fruits', 'unit' => 'kg', 'description' => 'Juicy mangoes'],
            ['name' => 'Oranges', 'category' => 'Fruits', 'unit' => 'kg', 'description' => 'Citrus oranges'],
            ['name' => 'Apples', 'category' => 'Fruits', 'unit' => 'kg', 'description' => 'Fresh apples'],
            ['name' => 'Pineapples', 'category' => 'Fruits', 'unit' => 'kg', 'description' => 'Sweet pineapples'],

            // Beverages
            ['name' => 'Coffee Beans', 'category' => 'Beverages', 'unit' => 'kg', 'description' => 'Arabica coffee beans'],
            ['name' => 'Tea Leaves', 'category' => 'Beverages', 'unit' => 'kg', 'description' => 'Fresh tea leaves'],
            ['name' => 'Cocoa Beans', 'category' => 'Beverages', 'unit' => 'kg', 'description' => 'Raw cocoa beans'],

            // Other
            ['name' => 'Cashews', 'category' => 'Nuts', 'unit' => 'kg', 'description' => 'Premium cashew nuts'],
            ['name' => 'Groundnuts', 'category' => 'Nuts', 'unit' => 'kg', 'description' => 'Peanut groundnuts'],
            ['name' => 'Cassava', 'category' => 'Tubers', 'unit' => 'kg', 'description' => 'Fresh cassava roots'],
            ['name' => 'Sweet Potatoes', 'category' => 'Tubers', 'unit' => 'kg', 'description' => 'Orange sweet potatoes'],
        ];

        foreach ($products as $product) {
            \App\Models\Product::create($product);
        }
    }
}
