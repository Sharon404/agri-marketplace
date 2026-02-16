<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('seller_id')->constrained('seller_profiles');
            $table->integer('quantity');
            $table->decimal('price_per_unit', 10, 2);
            $table->decimal('subtotal', 12, 2);
            $table->decimal('shipping_fee', 12, 2);
            $table->timestamps();

            $table->index('order_id');
            $table->index('seller_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
