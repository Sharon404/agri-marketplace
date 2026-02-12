<?php
// This script seeds products and creates test listings/requests/deals

// Set up a minimal Laravel environment to use Eloquent
require_once __DIR__ . '/bootstrap/app.php';

use App\Models\Product;
use App\Models\User;
use App\Models\FarmerListing;
use App\Models\BuyerRequest;
use App\Models\Deal;

// Use application kernel to get container
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);

// Create the products if they don't exist
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

echo "Seeding products...\n";
foreach ($products as $productName) {
    Product::firstOrCreate(['name' => $productName], [
        'category' => 'Agriculture',
        'unit' => 'kg',
        'description' => 'Fresh ' . $productName
    ]);
}

echo "Products seeded successfully.\n";

// Get test users
echo "\nFetching test users...\n";
$farmer = User::where('role', 'farmer')->where('approval_status', 'approved')->first();
$buyer = User::where('role', 'buyer')->where('approval_status', 'approved')->first();

if (!$farmer) {
    echo "ERROR: No approved farmer found in database\n";
    exit(1);
}
if (!$buyer) {
    echo "ERROR: No approved buyer found in database\n";
    exit(1);
}

echo "Found farmer: {$farmer->email}\n";
echo "Found buyer: {$buyer->email}\n";

// Create test listings
echo "\nCreating test farmer listings...\n";
$tomatoProduct = Product::where('name', 'Tomatoes')->first();
$potatoProduct = Product::where('name', 'Potatoes')->first();

FarmerListing::updateOrCreate(
    ['farmer_id' => $farmer->id, 'product_id' => $tomatoProduct->id],
    [
        'quantity' => 500,
        'price_per_unit' => 50,
        'location' => 'Nairobi County',
        'description' => 'Fresh organic tomatoes from our farm',
        'is_active' => true,
        'farmer_id' => $farmer->id,
        'product_id' => $tomatoProduct->id,
    ]
);

FarmerListing::updateOrCreate(
    ['farmer_id' => $farmer->id, 'product_id' => $potatoProduct->id],
    [
        'quantity' => 800,
        'price_per_unit' => 35,
        'location' => 'Kiambu County',
        'description' => 'Large high-quality potatoes',
        'is_active' => true,
        'farmer_id' => $farmer->id,
        'product_id' => $potatoProduct->id,
    ]
);

echo "Farmer listings created.\n";

// Create test buyer requests
echo "\nCreating test buyer requests...\n";
$onionProduct = Product::where('name', 'Onions')->first();
$carrotProduct = Product::where('name', 'Carrots')->first();

BuyerRequest::updateOrCreate(
    ['buyer_id' => $buyer->id, 'product_id' => $onionProduct->id],
    [
        'quantity' => 200,
        'max_price' => 45,
        'location' => 'Nairobi County',
        'description' => 'Need quality onions for restaurant supply',
        'is_active' => true,
        'buyer_id' => $buyer->id,
        'product_id' => $onionProduct->id,
    ]
);

BuyerRequest::updateOrCreate(
    ['buyer_id' => $buyer->id, 'product_id' => $carrotProduct->id],
    [
        'quantity' => 150,
        'max_price' => 40,
        'location' => 'Nairobi County',
        'description' => 'Fresh carrots needed weekly',
        'is_active' => true,
        'buyer_id' => $buyer->id,
        'product_id' => $carrotProduct->id,
    ]
);

echo "Buyer requests created.\n";

// Create test deals
echo "\nCreating test deals...\n";
$tomatolisting = FarmerListing::where('farmer_id', $farmer->id)->where('product_id', $tomatoProduct->id)->first();
$onionRequest = BuyerRequest::where('buyer_id', $buyer->id)->where('product_id', $onionProduct->id)->first();

if ($tomatolisting && $onionRequest) {
    Deal::updateOrCreate(
        ['farmer_listing_id' => $tomatolisting->id, 'buyer_request_id' => $onionRequest->id],
        [
            'agreed_quantity' => 100,
            'agreed_price' => 48,
            'status' => 'pending_farmer_confirmation',
            'farmer_listing_id' => $tomatolisting->id,
            'buyer_request_id' => $onionRequest->id,
        ]
    );
}

echo "Deals created.\n";
echo "\n✅ TEST DATA SEEDED SUCCESSFULLY!\n";
echo "\nDatabase now has:\n";
echo "- " . Product::count() . " products\n";
echo "- " . FarmerListing::count() . " farmer listings\n";
echo "- " . BuyerRequest::count() . " buyer requests\n";
echo "- " . Deal::count() . " deals\n";
