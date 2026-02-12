<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Product;
use App\Models\FarmerListing;
use App\Models\BuyerRequest;
use App\Models\Deal;

class CreateTestData extends Command
{
    protected $signature = 'test:create-data';
    
    protected $description = 'Create test farmer listings, buyer requests, and deals';

    public function handle()
    {
        $this->info('Creating test data...');

        // Get or create test users
        $farmer = User::where('role', 'farmer')->where('approval_status', 'approved')->first();
        $buyer = User::where('role', 'buyer')->where('approval_status', 'approved')->first();

        if (!$farmer) {
            $farmer = User::create([
                'name' => 'Test Farmer',
                'email' => 'testfarmer@test.com',
                'password' => bcrypt('password123'),
                'role' => 'farmer',
                'email_verified_at' => now(),
                'approval_status' => 'approved',
                'approved_at' => now(),
            ]);
        }

        if (!$buyer) {
            $buyer = User::create([
                'name' => 'Test Buyer',
                'email' => 'testbuyer@test.com',
                'password' => bcrypt('password123'),
                'role' => 'buyer',
                'email_verified_at' => now(),
                'approval_status' => 'approved',
                'approved_at' => now(),
            ]);
        }

        $this->info('Farmer: ' . $farmer->email);
        $this->info('Buyer: ' . $buyer->email);
        $this->info('Products: ' . Product::count());

        // Get products
        $tomato = Product::where('name', 'Tomatoes')->first();
        $potato = Product::where('name', 'Potatoes')->first();
        $onion = Product::where('name', 'Onions')->first();
        $carrot = Product::where('name', 'Carrots')->first();

        if (!$tomato || !$potato || !$onion || !$carrot) {
            $this->error('ERROR: Required products not found');
            return 1;
        }

        // Create farmer listings
        FarmerListing::updateOrCreate(
            ['farmer_id' => $farmer->id, 'product_id' => $tomato->id],
            [
                'quantity' => 500,
                'price_per_unit' => 50,
                'location' => 'Nairobi County',
                'description' => 'Fresh organic tomatoes from our farm',
                'is_active' => true,
            ]
        );

        FarmerListing::updateOrCreate(
            ['farmer_id' => $farmer->id, 'product_id' => $potato->id],
            [
                'quantity' => 800,
                'price_per_unit' => 35,
                'location' => 'Kiambu County',
                'description' => 'Large high-quality potatoes',
                'is_active' => true,
            ]
        );

        $this->info('Farmer listings: ' . FarmerListing::count());

        // Create buyer requests
        BuyerRequest::updateOrCreate(
            ['buyer_id' => $buyer->id, 'product_id' => $onion->id],
            [
                'quantity' => 200,
                'max_price' => 45,
                'location' => 'Nairobi County',
                'description' => 'Need quality onions for restaurant supply',
                'is_active' => true,
            ]
        );

        BuyerRequest::updateOrCreate(
            ['buyer_id' => $buyer->id, 'product_id' => $carrot->id],
            [
                'quantity' => 150,
                'max_price' => 40,
                'location' => 'Nairobi County',
                'description' => 'Fresh carrots needed weekly',
                'is_active' => true,
            ]
        );

        $this->info('Buyer requests: ' . BuyerRequest::count());

        // Create deals
        $listing1 = FarmerListing::where('farmer_id', $farmer->id)
            ->where('product_id', $tomato->id)
            ->first();
        $request1 = BuyerRequest::where('buyer_id', $buyer->id)
            ->where('product_id', $onion->id)
            ->first();

        if ($listing1 && $request1) {
            Deal::updateOrCreate(
                ['farmer_listing_id' => $listing1->id, 'buyer_request_id' => $request1->id],
                [
                    'agreed_quantity' => 100,
                    'agreed_price' => 48,
                    'status' => 'pending_farmer_confirmation',
                ]
            );
        }

        $this->info('Deals: ' . Deal::count());

        $this->info("\n✅ All test data created successfully!");
        return 0;
    }
}
