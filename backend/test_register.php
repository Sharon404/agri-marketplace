<?php

// Test registration
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$request = \Illuminate\Http\Request::create('/api/register', 'POST', [], [], [], [
    'HTTP_CONTENT_TYPE' => 'application/json',
], json_encode([
    'first_name' => 'Test',
    'last_name' => 'User',
    'email' => 'test' . time() . '@example.com',
    'phone' => '1234567890',
    'password' => 'password123',
    'role' => 'buyer',
]));

try {
    $response = $kernel->handle($request);
    echo "Status: " . $response->getStatusCode() . "\n";
    echo "Response: " . $response->getContent() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
