<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BuyerRequest>
 */
class BuyerRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'buyer_id' => \App\Models\User::factory()->create(['role' => 'buyer'])->id,
            'product_id' => \App\Models\Product::factory(),
            'quantity' => fake()->numberBetween(50, 5000),
            'target_price' => fake()->optional(0.7)->numberBetween(5, 300), // 70% have target price
            'delivery_location' => fake()->city() . ', ' . fake()->country(),
            'urgency' => fake()->randomElement(['low', 'medium', 'high']),
            'description' => fake()->sentence(),
            'is_active' => fake()->boolean(90), // 90% active
        ];
    }
}
