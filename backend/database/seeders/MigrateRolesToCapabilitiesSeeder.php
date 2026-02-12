<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\UserCapability;

class MigrateRolesToCapabilitiesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Alternative to migration: can be run multiple times safely.
     * Use this if you prefer seeder-based approach.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $stats = [
                'total' => 0,
                'migrated' => 0,
                'skipped' => 0,
                'farmers' => 0,
                'buyers' => 0,
                'admins' => 0,
                'agents' => 0,
            ];

            $users = User::all();
            $stats['total'] = $users->count();

            foreach ($users as $user) {
                // Check if already has granted capabilities
                $existingCapability = UserCapability::where('user_id', $user->id)->first();
                if ($existingCapability && ($existingCapability->can_buy || $existingCapability->can_sell)) {
                    $stats['skipped']++;
                    $this->command->info("⊘ Skipped: {$user->name} (already has capabilities)");
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
                $granted = [];

                switch ($user->role) {
                    case 'farmer':
                        $capability->can_sell = true;
                        $capability->sell_approved_at = now();
                        $capability->save();
                        $granted[] = 'can_sell';
                        $stats['farmers']++;
                        break;

                    case 'buyer':
                        $capability->can_buy = true;
                        $capability->buy_approved_at = now();
                        $capability->save();
                        $granted[] = 'can_buy';
                        $stats['buyers']++;
                        break;

                    case 'both':
                        $capability->can_buy = true;
                        $capability->can_sell = true;
                        $capability->buy_approved_at = now();
                        $capability->sell_approved_at = now();
                        $capability->save();
                        $granted[] = 'can_buy';
                        $granted[] = 'can_sell';
                        break;

                    case 'admin':
                        $capability->can_buy = true;
                        $capability->can_sell = true;
                        $capability->buy_approved_at = now();
                        $capability->sell_approved_at = now();
                        $capability->save();
                        $granted[] = 'can_buy';
                        $granted[] = 'can_sell';
                        $stats['admins']++;
                        break;

                    case 'agent':
                        $capability->can_buy = true;
                        $capability->can_sell = true;
                        $capability->buy_approved_at = now();
                        $capability->sell_approved_at = now();
                        $capability->save();
                        $granted[] = 'can_buy';
                        $granted[] = 'can_sell';
                        $stats['agents']++;
                        break;

                    default:
                        $this->command->warn("⚠ Unknown role '{$user->role}' for user: {$user->name}");
                        continue 2;
                }

                $stats['migrated']++;
                $this->command->info("✓ {$user->name} ({$user->role}): " . implode(', ', $granted));
            }

            // Display summary
            $this->command->newLine();
            $this->command->info('=== MIGRATION SUMMARY ===');
            $this->command->table(
                ['Metric', 'Count'],
                [
                    ['Total Users', $stats['total']],
                    ['Migrated', $stats['migrated']],
                    ['Skipped', $stats['skipped']],
                    ['', ''],
                    ['Farmers → can_sell', $stats['farmers']],
                    ['Buyers → can_buy', $stats['buyers']],
                    ['Admins → both', $stats['admins']],
                    ['Agents → both', $stats['agents']],
                ]
            );

            // Verification query
            $this->command->newLine();
            $this->command->info('=== VERIFICATION ===');
            $canBuyCount = UserCapability::where('can_buy', true)->count();
            $canSellCount = UserCapability::where('can_sell', true)->count();
            $this->command->info("Users with can_buy: {$canBuyCount}");
            $this->command->info("Users with can_sell: {$canSellCount}");
        });
    }
}
