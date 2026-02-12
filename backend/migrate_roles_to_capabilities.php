<?php

/**
 * Standalone migration script for role-to-capability transformation
 * 
 * Provides interactive safety checks and detailed reporting.
 * 
 * Usage:
 *   docker exec agri-backend-app php migrate_roles_to_capabilities.php
 *   docker exec agri-backend-app php migrate_roles_to_capabilities.php --dry-run
 *   docker exec agri-backend-app php migrate_roles_to_capabilities.php --rollback
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\UserCapability;
use Illuminate\Support\Facades\DB;

// Parse command line arguments
$dryRun = in_array('--dry-run', $argv);
$rollback = in_array('--rollback', $argv);

echo "\n";
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║     ROLE TO CAPABILITY MIGRATION SCRIPT                     ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo "\n";

if ($dryRun) {
    echo "🔍 DRY RUN MODE - No changes will be made\n\n";
}

if ($rollback) {
    echo "⚠️  ROLLBACK MODE - Will remove capability records\n\n";
}

// Pre-flight checks
echo "=== PRE-FLIGHT CHECKS ===\n";
$totalUsers = User::count();
$existingCapabilities = UserCapability::count();
$grantedCapabilities = UserCapability::where('can_buy', true)->orWhere('can_sell', true)->count();

echo "✓ Total users: {$totalUsers}\n";
echo "✓ Existing capability records: {$existingCapabilities}\n";
echo "✓ Capability records with grants: {$grantedCapabilities}\n";
echo "\n";

// Role breakdown
$roleStats = DB::table('users')
    ->select('role', DB::raw('count(*) as count'))
    ->groupBy('role')
    ->get();

echo "Role Distribution:\n";
foreach ($roleStats as $stat) {
    echo "  - {$stat->role}: {$stat->count}\n";
}
echo "\n";

// Rollback logic
if ($rollback) {
    $usersWithCapabilities = User::has('capability')->with('capability')->get();
    $removableCount = 0;
    
    foreach ($usersWithCapabilities as $user) {
        $capability = $user->capability;
        $shouldRemove = false;
        
        switch ($user->role) {
            case 'farmer':
                if ($capability->can_sell && !$capability->can_buy) {
                    $shouldRemove = true;
                }
                break;
            case 'buyer':
                if ($capability->can_buy && !$capability->can_sell) {
                    $shouldRemove = true;
                }
                break;
            case 'admin':
            case 'agent':
                if ($capability->can_buy && $capability->can_sell) {
                    $shouldRemove = true;
                }
                break;
        }
        
        if ($shouldRemove) {
            $removableCount++;
        }
    }
    
    echo "⚠️  ROLLBACK PREVIEW\n";
    echo "Capability records to remove: {$removableCount}\n";
    echo "\n";
    
    if (!$dryRun) {
        echo "Are you sure you want to rollback? Type 'yes' to confirm: ";
        $handle = fopen("php://stdin", "r");
        $line = trim(fgets($handle));
        fclose($handle);
        
        if ($line !== 'yes') {
            echo "❌ Rollback cancelled.\n";
            exit(0);
        }
        
        DB::transaction(function () use ($usersWithCapabilities) {
            $removedCount = 0;
            
            foreach ($usersWithCapabilities as $user) {
                $capability = $user->capability;
                $shouldRemove = false;
                
                switch ($user->role) {
                    case 'farmer':
                        if ($capability->can_sell && !$capability->can_buy) {
                            $shouldRemove = true;
                        }
                        break;
                    case 'buyer':
                        if ($capability->can_buy && !$capability->can_sell) {
                            $shouldRemove = true;
                        }
                        break;
                    case 'admin':
                    case 'agent':
                        if ($capability->can_buy && $capability->can_sell) {
                            $shouldRemove = true;
                        }
                        break;
                }
                
                if ($shouldRemove) {
                    echo "  ✓ Removed capabilities for: {$user->name} ({$user->role})\n";
                    $capability->delete();
                    $removedCount++;
                }
            }
            
            echo "\n✅ Rollback complete: {$removedCount} capability records removed\n";
        });
    }
    
    exit(0);
}

// Migration logic
echo "=== MIGRATION PLAN ===\n";

$migrationPlan = [
    'farmers' => 0,
    'buyers' => 0,
    'admins' => 0,
    'agents' => 0,
    'skip' => 0,
];

$users = User::all();
foreach ($users as $user) {
    $existingCapability = UserCapability::where('user_id', $user->id)->first();
    if ($existingCapability && ($existingCapability->can_buy || $existingCapability->can_sell)) {
        $migrationPlan['skip']++;
        continue;
    }
    
    switch ($user->role) {
        case 'farmer':
            $migrationPlan['farmers']++;
            break;
        case 'buyer':
            $migrationPlan['buyers']++;
            break;
        case 'admin':
            $migrationPlan['admins']++;
            break;
        case 'agent':
            $migrationPlan['agents']++;
            break;
    }
}

echo "Will migrate:\n";
echo "  • {$migrationPlan['farmers']} farmers → can_sell\n";
echo "  • {$migrationPlan['buyers']} buyers → can_buy\n";
echo "  • {$migrationPlan['admins']} admins → can_buy + can_sell\n";
echo "  • {$migrationPlan['agents']} agents → can_buy + can_sell\n";
echo "  • {$migrationPlan['skip']} users skipped (already have capabilities)\n";
echo "\n";

if ($dryRun) {
    echo "✓ Dry run complete. No changes made.\n";
    exit(0);
}

// Execute migration
echo "Are you sure you want to proceed? Type 'yes' to confirm: ";
$handle = fopen("php://stdin", "r");
$line = trim(fgets($handle));
fclose($handle);

if ($line !== 'yes') {
    echo "❌ Migration cancelled.\n";
    exit(0);
}

echo "\n=== EXECUTING MIGRATION ===\n";

DB::transaction(function () use ($users) {
    $stats = [
        'migrated' => 0,
        'skipped' => 0,
        'farmers' => 0,
        'buyers' => 0,
        'admins' => 0,
        'agents' => 0,
    ];
    
    foreach ($users as $user) {
        // Skip if already has granted capabilities
        $existingCapability = UserCapability::where('user_id', $user->id)->first();
        if ($existingCapability && ($existingCapability->can_buy || $existingCapability->can_sell)) {
            $stats['skipped']++;
            continue;
        }
        
        // Create or get capability record
        $capability = UserCapability::firstOrCreate(
            ['user_id' => $user->id],
            [
                'can_buy' => false,
                'can_sell' => false,
                'status' => 'active',
            ]
        );
        
        // Apply role-based capabilities
        switch ($user->role) {
            case 'farmer':
                $capability->can_sell = true;
                $capability->sell_approved_at = now();
                $capability->save();
                $stats['farmers']++;
                echo "  ✓ {$user->name} (farmer) → can_sell\n";
                break;
                
            case 'buyer':
                $capability->can_buy = true;
                $capability->buy_approved_at = now();
                $capability->save();
                $stats['buyers']++;
                echo "  ✓ {$user->name} (buyer) → can_buy\n";
                break;
                
            case 'admin':
                $capability->can_buy = true;
                $capability->can_sell = true;
                $capability->buy_approved_at = now();
                $capability->sell_approved_at = now();
                $capability->save();
                $stats['admins']++;
                echo "  ✓ {$user->name} (admin) → can_buy + can_sell\n";
                break;
                
            case 'agent':
                $capability->can_buy = true;
                $capability->can_sell = true;
                $capability->buy_approved_at = now();
                $capability->sell_approved_at = now();
                $capability->save();
                $stats['agents']++;
                echo "  ✓ {$user->name} (agent) → can_buy + can_sell\n";
                break;
        }
        
        $stats['migrated']++;
    }
    
    echo "\n";
    echo "╔══════════════════════════════════════════════════════════════╗\n";
    echo "║                   MIGRATION COMPLETE ✓                       ║\n";
    echo "╚══════════════════════════════════════════════════════════════╝\n";
    echo "\n";
    echo "Summary:\n";
    echo "  • Total migrated: {$stats['migrated']}\n";
    echo "  • Farmers: {$stats['farmers']}\n";
    echo "  • Buyers: {$stats['buyers']}\n";
    echo "  • Admins: {$stats['admins']}\n";
    echo "  • Agents: {$stats['agents']}\n";
    echo "  • Skipped: {$stats['skipped']}\n";
    echo "\n";
    
    // Verification
    $canBuyCount = UserCapability::where('can_buy', true)->where('status', 'active')->count();
    $canSellCount = UserCapability::where('can_sell', true)->where('status', 'active')->count();
    echo "Verification:\n";
    echo "  • Active users with can_buy: {$canBuyCount}\n";
    echo "  • Active users with can_sell: {$canSellCount}\n";
    echo "\n";
    echo "✅ All role data preserved in 'role' column\n";
    echo "✅ All changes committed in single transaction\n";
});
