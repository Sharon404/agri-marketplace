<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Test with farmer@example.com
$user = \App\Models\User::where('email', 'farmer@example.com')->first();

if (!$user) {
    echo json_encode(['error' => 'User not found']);
    exit;
}

// Simulate what login returns
echo json_encode([
    'message' => 'Login test response',
    'user' => [
        'id' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
        'phone' => $user->phone,
        'role' => $user->role,
        'email_verified' => $user->email_verified,
        'phone_verified' => $user->phone_verified,
    ],
    'jwt_claims' => $user->getJWTCustomClaims(),
    'raw_user_data' => [
        'has_role_property' => property_exists($user, 'role'),
        'role_value' => $user->role,
        'fillable' => $user->getFillable(),
    ]
], JSON_PRETTY_PRINT);
