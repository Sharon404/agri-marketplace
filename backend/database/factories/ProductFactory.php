<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $products = [
            ['name' => 'Rice', 'category' => 'Grains', 'unit' => 'kg'],
            ['name' => 'Wheat', 'category' => 'Grains', 'unit' => 'kg'],
            ['name' => 'Maize', 'category' => 'Grains', 'unit' => 'kg'],
            ['name' => 'Tomatoes', 'category' => 'Vegetables', 'unit' => 'kg'],
            ['name' => 'Potatoes', 'category' => 'Vegetables', 'unit' => 'kg'],
            ['name' => 'Onions', 'category' => 'Vegetables', 'unit' => 'kg'],
            ['name' => 'Bananas', 'category' => 'Fruits', 'unit' => 'kg'],
            ['name' => 'Mangoes', 'category' => 'Fruits', 'unit' => 'kg'],
            ['name' => 'Coffee Beans', 'category' => 'Beverages', 'unit' => 'kg'],
            ['name' => 'Tea Leaves', 'category' => 'Beverages', 'unit' => 'kg'],
        ];

        $product = fake()->randomElement($products);

        return [
            'name' => $product['name'],
            'category' => $product['category'],
            'unit' => $product['unit'],
            'description' => fake()->sentence(),
        ];
    }
}
