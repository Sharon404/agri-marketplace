<?php

/**
 * Auto-approve new users script
 * This allows newly registered users to immediately use the system
 * In production, you'll want manual admin approval instead
 */

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Find all unapproved users (except admins)
$users = \DB::table('users')
    ->where('role', '!=', 'admin')
    ->where('approval_status', '=', 'pending')
    ->where('created_at', '>', now()->subMinutes(5))
    ->orderBy('created_at', 'desc')
    ->get();

if ($users->count() === 0) {
    echo json_encode(['message' => 'No new pending users to approve']);
    exit;
}

$approved = 0;
foreach ($users as $user) {
    \DB::table('users')
        ->where('id', $user->id)
        ->update([
            'approval_status' => 'approved',
            'approved_by' => 1,
            'approved_at' => now(),
        ]);
    $approved++;
}

echo json_encode([
    'success' => true,
    'message' => "Auto-approved {$approved} new users",
    'users' => \DB::table('users')
        ->whereIn('id', $users->pluck('id')->toArray())
        ->select('id', 'email', 'role', 'approval_status')
        ->get()
        ->toArray(),
], JSON_PRETTY_PRINT);
