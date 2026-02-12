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
        Schema::create('user_capabilities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->boolean('can_buy')->default(false);
            $table->boolean('can_sell')->default(false);
            $table->timestamp('buy_requested_at')->nullable();
            $table->timestamp('sell_requested_at')->nullable();
            $table->timestamp('buy_approved_at')->nullable();
            $table->timestamp('sell_approved_at')->nullable();
            $table->enum('status', ['active', 'suspended'])->default('active');
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            // Unique constraint - one capability record per user
            $table->unique('user_id');

            // Indexes for performance
            $table->index('status');
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_capabilities');
    }
};
