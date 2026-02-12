<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\UserCapability;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Safely migrate existing user roles to capability-based system.
     * This does NOT modify or delete the role column.
     */
    public function up(): void
    {
        // Use database transaction for atomic operation
        DB::transaction(function () {
            $migratedCount = 0;
            $skippedCount = 0;

            // Get all users
            $users = User::all();

            foreach ($users as $user) {
                // Skip if capability record already exists with granted permissions
                $existingCapability = UserCapability::where('user_id', $user->id)->first();
                if ($existingCapability && ($existingCapability->can_buy || $existingCapability->can_sell)) {
                    $skippedCount++;
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

                // Map role to capabilities
                $updated = false;

                switch ($user->role) {
                    case 'farmer':
                        $capability->can_sell = true;
                        $capability->sell_approved_at = now();
                        $updated = true;
                        break;

                    case 'buyer':
                        $capability->can_buy = true;
                        $capability->buy_approved_at = now();
                        $updated = true;
                        break;

                    case 'both':
                        // Edge case: if role enum is later expanded to include 'both'
                        $capability->can_buy = true;
                        $capability->can_sell = true;
                        $capability->buy_approved_at = now();
                        $capability->sell_approved_at = now();
                        $updated = true;
                        break;

                    case 'admin':
                        // Admins get both capabilities by default
                        $capability->can_buy = true;
                        $capability->can_sell = true;
                        $capability->buy_approved_at = now();
                        $capability->sell_approved_at = now();
                        $updated = true;
                        break;

                    case 'agent':
                        // Agents typically facilitate transactions, give both capabilities
                        $capability->can_buy = true;
                        $capability->can_sell = true;
                        $capability->buy_approved_at = now();
                        $capability->sell_approved_at = now();
                        $updated = true;
                        break;

                    default:
                        // Unknown role: don't grant any capabilities
                        break;
                }

                if ($updated) {
                    $capability->save();
                    $migratedCount++;
                }
            }

            // Log migration results
            \Log::info("Role to Capability Migration Complete", [
                'migrated' => $migratedCount,
                'skipped' => $skippedCount,
                'total_users' => $users->count(),
            ]);

            echo "✓ Migrated {$migratedCount} users to capability system\n";
            echo "✓ Skipped {$skippedCount} users (already have capabilities)\n";
        });
    }

    /**
     * Reverse the migrations.
     * 
     * Safe rollback: removes capability records that match role mapping.
     * Only removes capabilities that were likely created by this migration.
     */
    public function down(): void
    {
        DB::transaction(function () {
            $removedCount = 0;

            $users = User::with('capability')->get();

            foreach ($users as $user) {
                if (!$user->capability) {
                    continue;
                }

                $capability = $user->capability;
                $shouldRemove = false;

                // Only remove if capability matches the role mapping
                switch ($user->role) {
                    case 'farmer':
                        // Remove if ONLY can_sell is true (as set by migration)
                        if ($capability->can_sell && !$capability->can_buy) {
                            $shouldRemove = true;
                        }
                        break;

                    case 'buyer':
                        // Remove if ONLY can_buy is true (as set by migration)
                        if ($capability->can_buy && !$capability->can_sell) {
                            $shouldRemove = true;
                        }
                        break;

                    case 'admin':
                    case 'agent':
                    case 'both':
                        // Remove if both capabilities are true
                        if ($capability->can_buy && $capability->can_sell) {
                            $shouldRemove = true;
                        }
                        break;
                }

                if ($shouldRemove) {
                    $capability->delete();
                    $removedCount++;
                }
            }

            \Log::info("Role to Capability Migration Rollback Complete", [
                'removed' => $removedCount,
            ]);

            echo "✓ Rolled back {$removedCount} capability records\n";
        });
    }
};
