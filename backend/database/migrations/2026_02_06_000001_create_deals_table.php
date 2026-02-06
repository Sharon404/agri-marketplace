<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('deals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farmer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('buyer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('farmer_listing_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('buyer_request_id')->nullable()->constrained()->onDelete('set null');
            
            $table->decimal('quantity', 10, 2);
            $table->decimal('agreed_price', 10, 2);
            $table->decimal('total_amount', 10, 2);
            
            $table->enum('status', ['pending', 'accepted', 'in_transit', 'delivered', 'completed', 'cancelled', 'disputed'])->default('pending');
            $table->enum('payment_status', ['unpaid', 'partial', 'paid', 'refunded'])->default('unpaid');
            
            $table->string('delivery_location');
            $table->date('delivery_date')->nullable();
            $table->text('delivery_notes')->nullable();
            
            $table->text('farmer_notes')->nullable();
            $table->text('buyer_notes')->nullable();
            
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            
            $table->timestamps();
            
            $table->index(['farmer_id', 'status']);
            $table->index(['buyer_id', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('deals');
    }
};
