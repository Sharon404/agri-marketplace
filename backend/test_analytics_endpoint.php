<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;
use App\Models\FarmerListing;
use App\Models\BuyerRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;

echo "===== DATABASE CONTENTS =====\n";
echo "Products: " . Product::count() . "\n";
echo "Farmer Listings: " . FarmerListing::count() . "\n";
echo "Buyer Requests: " . BuyerRequest::count() . "\n";

echo "\n===== SAMPLE PRODUCTS =====\n";
$products = Product::limit(3)->get();
foreach ($products as $p) {
    echo "ID {$p->id}: {$p->name}\n";
}

echo "\n===== SAMPLE FARMER LISTINGS =====\n";
$listings = FarmerListing::select('id', 'product_id', 'quantity', 'is_active')->limit(3)->get();
foreach ($listings as $l) {
    echo "ID {$l->id}: Product {$l->product_id}, Qty {$l->quantity}, Active: {$l->is_active}\n";
}

echo "\n===== TESTING BUYER ANALYTICS QUERY =====\n";
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

echo "Products found by buyer analytics query: " . count($products) . "\n";
if (count($products) > 0) {
    foreach ($products as $p) {
        echo "  - {$p->name}: suppliers={$p->supplier_count}, demand={$p->demand_count}\n";
    }
} else {
    echo "No products found!\n";
}

echo "\nTesting alternate query (without whereHas on farmerListings):\n";
$allProducts = Product::select('name')
    ->withCount([
        'farmerListings as supplier_count',
        'buyerRequests as demand_count'
    ])
    ->limit(5)
    ->get();
echo "Total products: " . count($allProducts) . "\n";
foreach ($allProducts as $p) {
    echo "  - {$p->name}: suppliers={$p->supplier_count}, demand={$p->demand_count}\n";
}

echo "\n===== TESTING VERIFIED FARMERS QUERY =====\n";
$farmers = User::whereHas('capability', function ($q) {
    $q->where('can_sell', true)->where('status', 'active');
})->count();
echo "Verified farmers (via capability): " . $farmers . "\n";

echo "\nUsers with can_sell capability:\n";
$userCaps = DB::table('user_capabilities')->where('can_sell', true)->where('status', 'active')->select('user_id', 'can_sell', 'status')->limit(5)->get();
foreach ($userCaps as $cap) {
    echo "  User {$cap->user_id}: can_sell={$cap->can_sell}, status={$cap->status}\n";
}

echo "\nAll user capabilities:\n";
$allCaps = DB::table('user_capabilities')->select('user_id', 'can_buy', 'can_sell', 'status')->limit(10)->get();
foreach ($allCaps as $cap) {
    echo "  User {$cap->user_id}: can_buy={$cap->can_buy}, can_sell={$cap->can_sell}, status={$cap->status}\n";
}

echo "\n===== TESTING API ENDPOINT DIRECTLY =====\n";
$user = User::where('role', 'buyer')->first();
echo "Test user: {$user->name} (ID: {$user->id})\n";

// Simulate an authenticated request to the controller
$controller = new App\Http\Controllers\Api\AnalyticsController();
auth()->setUser($user);

// Test the buyerAnalytics method directly
$request = new Illuminate\Http\Request();
$response = $controller->buyerAnalytics($request);
echo "\nDirect controller call response:\n";
echo $response->content() . "\n";
