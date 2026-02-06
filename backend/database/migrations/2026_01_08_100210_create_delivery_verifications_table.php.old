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
        Schema::create('delivery_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('logistics_job_id')->constrained()->onDelete('cascade');
            $table->foreignId('verified_by_id')->constrained('users')->onDelete('cascade');
            $table->string('verification_type'); // farmer_signature, buyer_receipt, photos, etc.
            $table->json('verification_data')->nullable();
            $table->timestamp('verified_at');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['logistics_job_id', 'verified_at']);
            $table->index(['verified_by_id', 'verified_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_verifications');
    }
};
