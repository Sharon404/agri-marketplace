<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seller_profiles', function (Blueprint $table) {
            // Seller identification & verification
            $table->string('tax_id')->nullable()->after('business_name');
            $table->string('national_id')->nullable()->after('tax_id');
            
            // Bank details for payouts
            $table->string('bank_name')->nullable()->after('national_id');
            $table->string('bank_account_name')->nullable()->after('bank_name');
            $table->string('bank_account_number')->nullable()->after('bank_account_name');
            
            // Address
            $table->string('business_address')->nullable()->after('logo_url');
            
            // Verification timestamps & notes
            $table->timestamp('verified_at')->nullable()->after('verification_status');
            $table->text('rejection_reason')->nullable()->after('verified_at');
            
            // Change default verification_status from NULL to pending
            $table->dropColumn('verification_status');
            $table->enum('verification_status', ['pending', 'verified', 'rejected'])->default('pending')->after('commission_rate');
        });
    }

    public function down(): void
    {
        Schema::table('seller_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'tax_id',
                'national_id',
                'bank_name',
                'bank_account_name',
                'bank_account_number',
                'business_address',
                'verified_at',
                'rejection_reason',
                'verification_status',
            ]);
            $table->enum('verification_status', ['pending', 'verified', 'rejected'])->default('pending');
        });
    }
};
