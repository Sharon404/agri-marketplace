<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deal_id')->constrained('deals')->onDelete('cascade');
            $table->foreignId('buyer_id')->constrained('users')->onDelete('cascade');
            
            $table->decimal('amount', 12, 2);
            
            // Status: pending, escrowed, released, refunded, failed
            $table->enum('status', ['pending', 'escrowed', 'released', 'refunded', 'failed'])->default('pending');
            
            $table->string('payment_method')->nullable(); // mpesa, card, bank_transfer, etc.
            $table->string('transaction_reference')->nullable();
            
            $table->timestamp('escrow_released_at')->nullable();
            
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            $table->index(['deal_id', 'status']);
            $table->index(['buyer_id', 'status']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
