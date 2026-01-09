<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\FarmerListing;
use App\Models\Product;

class FarmerListingTest extends TestCase
{
    use RefreshDatabase;

    private $farmer;
    private $token;
    private $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->farmer = User::factory()->create(['role' => 'farmer']);
        $this->token = auth()->login($this->farmer);
        $this->product = Product::factory()->create();
    }

    public function test_farmer_can_create_listing()
    {
        $listingData = [
            'product_id' => $this->product->id,
            'quantity' => 100,
            'price_per_unit' => 2.50,
            'location' => 'Farm Location',
            'description' => 'Fresh produce',
            'harvest_date' => now()->toDateString(),
            'expiry_date' => now()->addDays(7)->toDateString(),
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/farmer-listings', $listingData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'id',
                    'product_id',
                    'quantity',
                    'price_per_unit',
                    'location',
                    'status',
                ]);

        $this->assertDatabaseHas('farmer_listings', [
            'product_id' => $this->product->id,
            'quantity' => 100,
            'price_per_unit' => 2.50,
        ]);
    }

    public function test_user_can_view_listings()
    {
        FarmerListing::factory()->count(3)->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/farmer-listings');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'product',
                            'quantity',
                            'price_per_unit',
                            'location',
                            'status',
                        ]
                    ],
                    'links',
                    'meta',
                ]);
    }

    public function test_listing_requires_valid_product()
    {
        $listingData = [
            'product_id' => 999, // Non-existent product
            'quantity' => 100,
            'price_per_unit' => 2.50,
            'location' => 'Farm Location',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/farmer-listings', $listingData);

        $response->assertStatus(422);
    }

    public function test_only_farmers_can_create_listings()
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $buyerToken = auth()->login($buyer);

        $listingData = [
            'product_id' => $this->product->id,
            'quantity' => 100,
            'price_per_unit' => 2.50,
            'location' => 'Farm Location',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $buyerToken,
        ])->postJson('/api/farmer-listings', $listingData);

        $response->assertStatus(403);
    }
}