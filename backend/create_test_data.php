<?php

use App\Models\User;
use App\Models\Product;
use App\Models\FarmerListing;
use App\Models\BuyerRequest;
use App\Models\Deal;

// Get or create test users
$farmer = User::firstOrCreate(
    ['email' => 'testfarmer@test.com'],
    [
        'name' => 'Test Farmer',
        'password' => bcrypt('password'),
        'role' => 'farmer',
        'email_verified_at' => now(),
        'approval_status' => 'approved',
        'approved_at' => now(),
    ]
);

$buyer = User::firstOrCreate(
    ['email' => 'testbuyer@test.com'],
    [
        'name' => 'Test Buyer',
        'password' => bcrypt('password'),
        'role' => 'buyer',
        'email_verified_at' => now(),
        'approval_status' => 'approved',
        'approved_at' => now(),
    ]
);

echo "Farmer: " . $farmer->email . " (ID: {$farmer->id})\n";
echo "Buyer: " . $buyer->email . " (ID: {$buyer->id})\n";
echo "Products count: " . Product::count() . "\n";

// Get products
$tomato = Product::where('name', 'Tomatoes')->first();
$potato = Product::where('name', 'Potatoes')->first();
$onion = Product::where('name', 'Onions')->first();
$carrot = Product::where('name', 'Carrots')->first();

if (!$tomato || !$potato || !$onion || !$carrot) {
    echo "ERROR: Not all products found\n";
    exit(1);
}

// Create farmer listings
$listing1 = FarmerListing::updateOrCreate(
    ['farmer_id' => $farmer->id, 'product_id' => $tomato->id],
    [
        'quantity' => 500,
        'price_per_unit' => 50,
        'location' => 'Nairobi County',
        'description' => 'Fresh organic tomatoes from our farm',
        'is_active' => true,
    ]
);

$listing2 = FarmerListing::updateOrCreate(
    ['farmer_id' => $farmer->id, 'product_id' => $potato->id],
    [
        'quantity' => 800,
        'price_per_unit' => 35,
        'location' => 'Kiambu County',
        'description' => 'Large high-quality potatoes',
        'is_active' => true,
    ]
);

echo "Farmer listings created: " . FarmerListing::count() . "\n";

// Create buyer requests
$request1 = BuyerRequest::updateOrCreate(
    ['buyer_id' => $buyer->id, 'product_id' => $onion->id],
    [
        'quantity' => 200,
        'max_price' => 45,
        'location' => 'Nairobi County',
        'description' => 'Need quality onions for restaurant supply',
        'is_active' => true,
    ]
);

$request2 = BuyerRequest::updateOrCreate(
    ['buyer_id' => $buyer->id, 'product_id' => $carrot->id],
    [
        'quantity' => 150,
        'max_price' => 40,
        'location' => 'Nairobi County',
        'description' => 'Fresh carrots needed weekly',
        'is_active' => true,
    ]
);

echo "Buyer requests created: " . BuyerRequest::count() . "\n";

// Create deals
$deal = Deal::updateOrCreate(
    ['farmer_listing_id' => $listing1->id, 'buyer_request_id' => $request1->id],
    [
        'agreed_quantity' => 100,
        'agreed_price' => 48,
        'status' => 'pending_farmer_confirmation',
    ]
);

echo "Deals created: " . Deal::count() . "\n";

echo "\n✅ ALL TEST DATA CREATED SUCCESSFULLY!\n";
