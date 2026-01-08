<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FarmerListing>
 */
class FarmerListingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'farmer_id' => \App\Models\User::factory()->create(['role' => 'farmer'])->id,
            'product_id' => \App\Models\Product::factory(),
            'quantity' => fake()->numberBetween(100, 10000),
            'unit_price' => fake()->numberBetween(10, 500),
            'location' => fake()->city() . ', ' . fake()->country(),
            'availability_date' => fake()->dateTimeBetween('now', '+30 days'),
            'description' => fake()->sentence(),
            'is_active' => fake()->boolean(80), // 80% active
        ];
    }
}
