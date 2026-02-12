<?php

// Load laravel autoloader and bootstrap
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Approve first few non-admin users
$result = \DB::table('users')
    ->whereIn('id', [2, 3, 4, 5])  // farmer@example.com, buyer@example.com, farmer1@example.com, testfarmer2@test.com
    ->update([
        'approval_status' => 'approved',
        'approved_by' => 1,  // approved by admin (id 1)
        'approved_at' => now(),
    ]);

// Also approve the admin user
\DB::table('users')
    ->where('id', 1)
    ->update(['approval_status' => 'approved', 'approved_by' => 1]);

echo json_encode([
    'status' => 'success',
    'message' => "Updated {$result} users to 'approved' status",
    'users' => \DB::table('users')
        ->select('id', 'email', 'role', 'approval_status')
        ->limit(5)
        ->get()
        ->toArray(),
], JSON_PRETTY_PRINT);
