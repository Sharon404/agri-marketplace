<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // Profile enhancements
            $table->string('profile_image')->nullable()->after('phone');
            $table->text('bio')->nullable()->after('profile_image');
            $table->string('business_name')->nullable()->after('bio');
            $table->string('business_registration')->nullable()->after('business_name');
            
            // Location details
            $table->string('county')->nullable()->after('location');
            $table->string('sub_county')->nullable()->after('county');
            $table->decimal('latitude', 10, 8)->nullable()->after('sub_county');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            
            // Account status
            $table->boolean('is_verified')->default(false)->after('email_verified');
            $table->enum('verification_level', ['none', 'email', 'phone', 'id', 'business'])->default('none')->after('is_verified');
            $table->boolean('is_active')->default(true)->after('verification_level');
            $table->timestamp('last_active_at')->nullable()->after('is_active');
            
            // Stats (will be added after reviews migration adds average_rating and total_reviews)
            $table->integer('total_deals')->default(0)->after('total_reviews');
            $table->integer('successful_deals')->default(0)->after('total_deals');
            $table->decimal('success_rate', 5, 2)->default(0)->after('successful_deals');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'profile_image', 'bio', 'business_name', 'business_registration',
                'county', 'sub_county', 'latitude', 'longitude',
                'is_verified', 'verification_level', 'is_active', 'last_active_at',
                'total_deals', 'successful_deals', 'success_rate'
            ]);
        });
    }
};
