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
            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone')->nullable()->unique()->after('email');
            }
            if (!Schema::hasColumn('users', 'email_verified')) {
                $table->boolean('email_verified')->default(false)->after('email_verified_at');
            }
            if (!Schema::hasColumn('users', 'phone_verified')) {
                $table->boolean('phone_verified')->default(false)->after('email_verified');
            }
            if (!Schema::hasColumn('users', 'role')) {
                $table->enum('role', ['farmer', 'buyer', 'admin', 'agent'])->default('farmer')->after('phone_verified');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'email_verified', 'phone_verified', 'role']);
        });
    }
};
