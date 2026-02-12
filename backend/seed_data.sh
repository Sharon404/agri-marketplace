#!/bin/bash

# Run artisan commands to seed data
php artisan tinker <<'EOF'

use App\Models\Product;
use App\Models\User;
use App\Models\FarmerListing;
use App\Models\BuyerRequest;
use App\Models\Deal;

echo "Starting data seed...\n";

// Create products
$products = [
    'Tomatoes',
    'Potatoes',
    'Onions',
    'Carrots',
    'Bananas',
    'Mangoes',
    'Rice',
    'Maize',
    'Cabbages', 
    'Peppers',
    'Beans',
    'Maize Flour',
];

foreach ($products as $name) {
    Product::firstOrCreate(['name' => $name], [
        'category' => 'Agriculture',
        'unit' => 'kg',
        'description' => 'Fresh ' . $name
    ]);
}

echo "Products created: " . Product::count() . "\n";

// Get test users
$farmer = User::where('role', 'farmer')->where('approval_status', 'approved')->first();
$buyer = User::where('role', 'buyer')->where('approval_status', 'approved')->first();

if (!$farmer || !$buyer) {
    echo "ERROR: Need approved farmer and buyer\n";
    exit;
}

echo "Found farmer: {$farmer->email}\n";
echo "Found buyer: {$buyer->email}\n";

// Create listings
$tomato = Product::where('name', 'Tomatoes')->first();
$potato = Product::where('name', 'Potatoes')->first();

FarmerListing::updateOrCreate(
    ['farmer_id' => $farmer->id, 'product_id' => $tomato->id],
    [
        'quantity' => 500,
        'price_per_unit' => 50,
        'location' => 'Nairobi County',
        'description' => 'Fresh organic tomatoes',
        'is_active' => true,
    ]
);

FarmerListing::updateOrCreate(
    ['farmer_id' => $farmer->id, 'product_id' => $potato->id],
    [
        'quantity' => 800,
        'price_per_unit' => 35,
        'location' => 'Kiambu County',
        'description' => 'High-quality potatoes',
        'is_active' => true,
    ]
);

echo "Farmer listings created: " . FarmerListing::count() . "\n";

// Create requests
$onion = Product::where('name', 'Onions')->first();
$carrot = Product::where('name', 'Carrots')->first();

BuyerRequest::updateOrCreate(
    ['buyer_id' => $buyer->id, 'product_id' => $onion->id],
    [
        'quantity' => 200,
        'max_price' => 45,
        'location' => 'Nairobi County',
        'description' => 'Need quality onions',
        'is_active' => true,
    ]
);

BuyerRequest::updateOrCreate(
    ['buyer_id' => $buyer->id, 'product_id' => $carrot->id],
    [
        'quantity' => 150,
        'max_price' => 40,
        'location' => 'Nairobi County',
        'description' => 'Fresh carrots weekly',
        'is_active' => true,
    ]
);

echo "Buyer requests created: " . BuyerRequest::count() . "\n";

// Create deals
$listing = FarmerListing::where('farmer_id', $farmer->id)->first();
$request = BuyerRequest::where('buyer_id', $buyer->id)->first();

if ($listing && $request) {
    Deal::updateOrCreate(
        ['farmer_listing_id' => $listing->id, 'buyer_request_id' => $request->id],
        [
            'agreed_quantity' => 100,
            'agreed_price' => 48,
            'status' => 'pending_farmer_confirmation',
        ]
    );
}

echo "Deals created: " . Deal::count() . "\n";
echo "\n✅ Data seeded successfully!\n";

EOF
