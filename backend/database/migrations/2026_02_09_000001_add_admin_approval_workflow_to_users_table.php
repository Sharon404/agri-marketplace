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
        Schema::table('users', function (Blueprint $table) {
            // Add admin approval workflow columns if they don't exist
            if (!Schema::hasColumn('users', 'approval_status')) {
                $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('pending')->after('phone_verified');
            }
            
            if (!Schema::hasColumn('users', 'approved_by')) {
                // Foreign key to users table (admin who approved)
                $table->unsignedBigInteger('approved_by')->nullable()->after('approval_status');
            }
            
            if (!Schema::hasColumn('users', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
            
            if (!Schema::hasColumn('users', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('approved_at');
            }
        });

        // Add foreign key constraint separately to avoid issues
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'approved_by')) {
                try {
                    $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
                } catch (\Exception $e) {
                    // Foreign key might already exist, ignore error
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop foreign key first
            if (Schema::hasColumn('users', 'approved_by')) {
                try {
                    $table->dropForeign(['approved_by']);
                } catch (\Exception $e) {
                    // Foreign key might not exist, ignore error
                }
            }
            
            // Drop columns if they exist
            if (Schema::hasColumn('users', 'approval_status')) {
                $table->dropColumn('approval_status');
            }
            if (Schema::hasColumn('users', 'approved_by')) {
                $table->dropColumn('approved_by');
            }
            if (Schema::hasColumn('users', 'approved_at')) {
                $table->dropColumn('approved_at');
            }
            if (Schema::hasColumn('users', 'rejection_reason')) {
                $table->dropColumn('rejection_reason');
            }
        });
    }
};
