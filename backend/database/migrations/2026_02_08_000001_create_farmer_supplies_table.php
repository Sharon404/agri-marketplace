<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('farmer_supplies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farmer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            
            $table->decimal('quantity_available', 12, 2);
            $table->string('unit')->default('kg'); // kg, tons, bags, etc.
            $table->decimal('price_per_unit', 10, 2);
            
            $table->date('available_from');
            $table->date('available_until');
            
            $table->text('description')->nullable();
            $table->text('delivery_terms')->nullable();
            
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            
            $table->index(['farmer_id', 'is_active']);
            $table->index(['product_id', 'is_active']);
            $table->index(['available_from', 'available_until']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('farmer_supplies');
    }
};
