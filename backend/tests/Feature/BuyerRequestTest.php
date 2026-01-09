<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\BuyerRequest;
use App\Models\Product;

class BuyerRequestTest extends TestCase
{
    use RefreshDatabase;

    private $buyer;
    private $token;
    private $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->buyer = User::factory()->create(['role' => 'buyer']);
        $this->token = auth()->login($this->buyer);
        $this->product = Product::factory()->create();
    }

    public function test_buyer_can_create_request()
    {
        $requestData = [
            'product_id' => $this->product->id,
            'quantity' => 50,
            'max_price_per_unit' => 3.00,
            'location' => 'Market Location',
            'description' => 'Need fresh produce',
            'delivery_date' => now()->addDays(3)->toDateString(),
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/buyer-requests', $requestData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'id',
                    'product_id',
                    'quantity',
                    'max_price_per_unit',
                    'location',
                    'status',
                ]);

        $this->assertDatabaseHas('buyer_requests', [
            'product_id' => $this->product->id,
            'quantity' => 50,
            'max_price_per_unit' => 3.00,
        ]);
    }

    public function test_user_can_view_requests()
    {
        BuyerRequest::factory()->count(3)->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/buyer-requests');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'product',
                            'quantity',
                            'max_price_per_unit',
                            'location',
                            'status',
                        ]
                    ],
                    'links',
                    'meta',
                ]);
    }

    public function test_request_requires_valid_product()
    {
        $requestData = [
            'product_id' => 999, // Non-existent product
            'quantity' => 50,
            'max_price_per_unit' => 3.00,
            'location' => 'Market Location',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/buyer-requests', $requestData);

        $response->assertStatus(422);
    }

    public function test_only_buyers_can_create_requests()
    {
        $farmer = User::factory()->create(['role' => 'farmer']);
        $farmerToken = auth()->login($farmer);

        $requestData = [
            'product_id' => $this->product->id,
            'quantity' => 50,
            'max_price_per_unit' => 3.00,
            'location' => 'Market Location',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $farmerToken,
        ])->postJson('/api/buyer-requests', $requestData);

        $response->assertStatus(403);
    }
}