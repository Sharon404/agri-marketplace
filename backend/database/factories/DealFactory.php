<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Deal>
 */
class DealFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $farmerListing = \App\Models\FarmerListing::factory()->create();
        $buyerRequest = \App\Models\BuyerRequest::factory()->create();

        return [
            'farmer_listing_id' => $farmerListing->id,
            'buyer_request_id' => $buyerRequest->id,
            'broker_id' => \App\Models\User::factory()->create(['role' => fake()->randomElement(['admin', 'agent'])])->id,
            'agreed_quantity' => min($farmerListing->quantity, $buyerRequest->quantity),
            'agreed_price' => ($farmerListing->unit_price + ($buyerRequest->target_price ?? $farmerListing->unit_price)) / 2,
            'status' => fake()->randomElement(['pending', 'negotiated', 'accepted', 'logistics_assigned', 'delivered', 'completed']),
            'notes' => fake()->optional(0.3)->sentence(),
        ];
    }
}
