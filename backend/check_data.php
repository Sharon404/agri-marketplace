<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo json_encode([
    'farmer_listings' => \DB::table('farmer_listings')->count(),
    'buyer_requests' => \DB::table('buyer_requests')->count(),
    'deals' => \DB::table('deals')->count(),
    'products' => \DB::table('products')->count(),
    'users' => [
        'total' => \DB::table('users')->count(),
        'farmers' => \DB::table('users')->where('role', 'farmer')->count(),
        'buyers' => \DB::table('users')->where('role', 'buyer')->count(),
        'approved' => \DB::table('users')->where('approval_status', 'approved')->count(),
        'pending' => \DB::table('users')->where('approval_status', 'pending')->count(),
    ]
], JSON_PRETTY_PRINT);
