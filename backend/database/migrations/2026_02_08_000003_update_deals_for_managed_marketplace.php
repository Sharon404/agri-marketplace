<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            // Add farmer_supply_id for managed marketplace
            if (!Schema::hasColumn('deals', 'farmer_supply_id')) {
                $table->foreignId('farmer_supply_id')->nullable()->after('farmer_listing_id')
                    ->constrained('farmer_supplies')->onDelete('set null');
            }

            // Add confirmation tracking
            if (!Schema::hasColumn('deals', 'buyer_confirmed_at')) {
                $table->timestamp('buyer_confirmed_at')->nullable()->after('accepted_at');
            }
            if (!Schema::hasColumn('deals', 'farmer_confirmed_at')) {
                $table->timestamp('farmer_confirmed_at')->nullable()->after('buyer_confirmed_at');
            }

            // Add admin reference
            if (!Schema::hasColumn('deals', 'admin_notes')) {
                $table->text('admin_notes')->nullable()->after('buyer_notes');
            }
            if (!Schema::hasColumn('deals', 'created_by_admin')) {
                $table->boolean('created_by_admin')->default(true)->after('admin_notes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            if (Schema::hasColumn('deals', 'farmer_supply_id')) {
                $table->dropConstrainedForeignId('farmer_supply_id');
            }
            if (Schema::hasColumn('deals', 'buyer_confirmed_at')) {
                $table->dropColumn('buyer_confirmed_at');
            }
            if (Schema::hasColumn('deals', 'farmer_confirmed_at')) {
                $table->dropColumn('farmer_confirmed_at');
            }
            if (Schema::hasColumn('deals', 'admin_notes')) {
                $table->dropColumn('admin_notes');
            }
            if (Schema::hasColumn('deals', 'created_by_admin')) {
                $table->dropColumn('created_by_admin');
            }
        });
    }
};
