<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $deal = \App\Models\Deal::factory()->create();
        $amount = $deal->agreed_quantity * $deal->agreed_price;
        $status = fake()->randomElement(['initiated', 'held', 'released', 'refunded']);

        $data = [
            'deal_id' => $deal->id,
            'amount' => $amount,
            'currency' => 'USD',
            'status' => $status,
            'psp_reference' => 'PSP_' . fake()->uuid(),
            'psp_provider' => fake()->randomElement(['stripe', 'paypal', 'local_psp']),
            'notes' => fake()->optional(0.2)->sentence(),
        ];

        // Set timestamps based on status
        if ($status === 'held') {
            $data['held_at'] = fake()->dateTimeBetween('-30 days', 'now');
        } elseif ($status === 'released') {
            $data['held_at'] = fake()->dateTimeBetween('-30 days', '-1 day');
            $data['released_at'] = fake()->dateTimeBetween($data['held_at'], 'now');
        } elseif ($status === 'refunded') {
            $data['held_at'] = fake()->dateTimeBetween('-30 days', '-1 day');
            $data['refunded_at'] = fake()->dateTimeBetween($data['held_at'], 'now');
        }

        return $data;
    }
}
