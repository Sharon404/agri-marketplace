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
            // Add reset token fields if they don't exist
            if (!Schema::hasColumn('users', 'reset_token')) {
                $table->string('reset_token')->nullable()->after('password');
            }
            if (!Schema::hasColumn('users', 'reset_token_expires_at')) {
                $table->timestamp('reset_token_expires_at')->nullable()->after('reset_token');
            }
            // Add 2FA fields for future use
            if (!Schema::hasColumn('users', '2fa_enabled')) {
                $table->boolean('2fa_enabled')->default(false)->after('reset_token_expires_at');
            }
            if (!Schema::hasColumn('users', '2fa_secret')) {
                $table->string('2fa_secret')->nullable()->after('2fa_enabled');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'reset_token',
                'reset_token_expires_at',
                '2fa_enabled',
                '2fa_secret',
            ]);
        });
    }
};
