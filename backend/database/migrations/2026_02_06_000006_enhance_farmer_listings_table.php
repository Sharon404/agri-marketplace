<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('farmer_listings', function (Blueprint $table) {
            // Pricing
            $table->decimal('min_order_quantity', 10, 2)->nullable()->after('price');
            $table->decimal('bulk_discount_percentage', 5, 2)->nullable()->after('min_order_quantity');
            $table->decimal('bulk_discount_threshold', 10, 2)->nullable()->after('bulk_discount_percentage');
            
            // Product details
            $table->string('unit')->default('kg')->after('quantity');
            $table->enum('quality_grade', ['A', 'B', 'C'])->nullable()->after('unit');
            $table->boolean('is_organic')->default(false)->after('quality_grade');
            $table->json('certifications')->nullable()->after('is_organic');
            
            // Images
            $table->json('images')->nullable()->after('certifications');
            
            // Availability
            $table->date('available_from')->nullable()->after('is_active');
            $table->date('available_until')->nullable()->after('available_from');
            $table->enum('harvest_status', ['pre_harvest', 'ready', 'harvested'])->default('ready')->after('available_until');
            
            // Delivery
            $table->boolean('delivery_available')->default(false)->after('location');
            $table->decimal('delivery_radius_km', 6, 2)->nullable()->after('delivery_available');
            $table->decimal('delivery_cost_per_km', 8, 2)->nullable()->after('delivery_radius_km');
            
            // Stats
            $table->integer('views_count')->default(0)->after('harvest_status');
            $table->integer('inquiries_count')->default(0)->after('views_count');
            $table->timestamp('last_viewed_at')->nullable()->after('inquiries_count');
        });
    }

    public function down()
    {
        Schema::table('farmer_listings', function (Blueprint $table) {
            $table->dropColumn([
                'min_order_quantity', 'bulk_discount_percentage', 'bulk_discount_threshold',
                'unit', 'quality_grade', 'is_organic', 'certifications', 'images',
                'available_from', 'available_until', 'harvest_status',
                'delivery_available', 'delivery_radius_km', 'delivery_cost_per_km',
                'views_count', 'inquiries_count', 'last_viewed_at'
            ]);
        });
    }
};
