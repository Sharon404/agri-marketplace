<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update user_capabilities table if needed for rejection tracking
        Schema::table('user_capabilities', function (Blueprint $table) {
            // Add columns if they don't exist
            if (!Schema::hasColumn('user_capabilities', 'buy_rejected_at')) {
                $table->timestamp('buy_rejected_at')->nullable()->after('buy_approved_at');
            }
            if (!Schema::hasColumn('user_capabilities', 'sell_rejected_at')) {
                $table->timestamp('sell_rejected_at')->nullable()->after('sell_approved_at');
            }
            if (!Schema::hasColumn('user_capabilities', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('sell_rejected_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_capabilities', function (Blueprint $table) {
            $table->dropColumn(['buy_rejected_at', 'sell_rejected_at', 'rejection_reason']);
        });
    }
};
