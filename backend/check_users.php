<?php

// Load laravel autoloader and bootstrap
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Get users
$users = \DB::table('users')->select('id', 'email', 'role', 'approval_status', 'phone_verified')->limit(5)->get();

echo json_encode([
    'status' => 'success',
    'count' => $users->count(),
    'users' => $users->toArray(),
], JSON_PRETTY_PRINT);
