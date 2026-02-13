#!/usr/bin/env php
<?php

// Test analytics data retrieval
require_once __DIR__ . '/bootstrap/app.php';

use App\Models\User;
use App\Models\UserCapability;
use App\Models\Product;
use App\Models\FarmerListing;
use App\Models\BuyerRequest;

echo "\n=== ANALYTICS DATA DIAGNOSTIC ===\n\n";

// 1. Check all users
$totalUsers = User::count();
echo "Total Users: $totalUsers\n";

// 2. Check capabilities
$totalCapabilities = UserCapability::count();
echo "Total Capabilities: $totalCapabilities\n";

// 3. Check users with can_buy=true
$buyersCount = User::whereHas('capability', function ($q) {
    $q->where('can_buy', true)->where('status', 'active');
})->count();
echo "Users with can_buy=true (Active): $buyersCount\n";

// 4. Check users with can_sell=true
$farmersCount = User::whereHas('capability', function ($q) {
    $q->where('can_sell', true)->where('status', 'active');
})->count();
echo "Users with can_sell=true (Active): $farmersCount\n";

// 5. Check products with farmer listings
$productsWithListings = Product::whereHas('farmerListings', function ($query) {
    $query->where('is_active', true);
})->count();
echo "\nProducts with Active Listings: $productsWithListings\n";

// 6. Check products with buyer requests
$productsWithRequests = Product::whereHas('buyerRequests')->count();
echo "Products with Buyer Requests: $productsWithRequests\n";

// 7. Check actual farmer listings count
$listingsCount = FarmerListing::where('is_active', true)->count();
echo "Active Farmer Listings: $listingsCount\n";

// 8. Check actual buyer requests count
$requestsCount = BuyerRequest::count();
echo "Total Buyer Requests: $requestsCount\n";

// 9. Get product highlights like the query does
$products = Product::select('name')
    ->withCount([
        'farmerListings as supplier_count',
        'buyerRequests as demand_count'
    ])
    ->whereHas('farmerListings', function ($query) {
        $query->where('is_active', true);
    })
    ->orderBy('supplier_count', 'desc')
    ->limit(5)
    ->get();

echo "\nTop 5 Products with Active Listings:\n";
if ($products->isEmpty()) {
    echo "  (No products with active listings)\n";
} else {
    foreach ($products as $product) {
        echo "  - {$product->name}: {$product->supplier_count} suppliers, {$product->demand_count} requests\n";
    }
}

// 10. Check capabilities table details
echo "\n=== FIRST 5 CAPABILITY RECORDS ===\n";
$caps = UserCapability::limit(5)->get();
if ($caps->isEmpty()) {
    echo "  (No capability records)\n";
} else {
    foreach ($caps as $cap) {
        $user = $cap->user;
        echo "User ID {$cap->user_id} ({$user->name}): can_buy={$cap->can_buy}, can_sell={$cap->can_sell}, status={$cap->status}\n";
    }
}

echo "\n";
?>
