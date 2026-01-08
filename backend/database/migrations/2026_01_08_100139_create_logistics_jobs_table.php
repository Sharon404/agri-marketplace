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
        Schema::create('logistics_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deal_id')->constrained()->onDelete('cascade');
            $table->foreignId('assigned_agent_id')->constrained('users')->onDelete('cascade');
            $table->string('pickup_location');
            $table->string('delivery_location');
            $table->timestamp('scheduled_pickup_at');
            $table->timestamp('scheduled_delivery_at');
            $table->timestamp('actual_pickup_at')->nullable();
            $table->timestamp('actual_delivery_at')->nullable();
            $table->enum('status', ['assigned', 'in_transit', 'delivered', 'failed'])->default('assigned');
            $table->string('vehicle_details')->nullable();
            $table->string('tracking_number')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['deal_id', 'status']);
            $table->index(['assigned_agent_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logistics_jobs');
    }
};
