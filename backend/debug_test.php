<?php

// Simple test to debug the registration issue
echo "Testing registration issue...\n\n";

// At this point, the app.php will have bootstrapped Laravel
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

// Get the database connection using Facade
try {
    $db = \Illuminate\Support\Facades\DB::table('users');
    $users = $db->count();
    echo "✓ Database connection successful\n";
    echo "✓ Total users in database: $users\n";
} catch (Exception $e) {
    echo "✗ Database error: " . $e->getMessage() . "\n";
    echo "✗ Trace: " . substr($e->getTraceAsString(), 0, 500) . "...\n";
    exit(1);
}

// Try creating a test user
try {
    $user = \App\Models\User::create([
        'first_name' => 'Debug',
        'last_name' => 'Test',
        'email' => 'debug_' . time() . '@test.com',
        'phone' => '9999999999',
        'password' => bcrypt('password123'),
        'role' => 'buyer',
        'status' => 'active',
    ]);
    echo "✓ User created successfully\n";
    echo "✓ User ID: {$user->id}, Email: {$user->email}\n";
} catch (Exception $e) {
    echo "✗ User creation error: " . $e->getMessage() . "\n";
    echo "✗ File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}

echo "\n✓ All tests passed!\n";
