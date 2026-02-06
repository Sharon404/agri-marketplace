<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('buyer_requests', function (Blueprint $table) {
            // Requirements
            if (!Schema::hasColumn('buyer_requests', 'unit')) {
                $table->string('unit')->default('kg');
            }
            if (!Schema::hasColumn('buyer_requests', 'needed_by')) {
                $table->date('needed_by')->nullable();
            }
            if (!Schema::hasColumn('buyer_requests', 'urgency')) {
                $table->enum('urgency', ['low', 'medium', 'high', 'urgent'])->default('medium');
            }
            if (!Schema::hasColumn('buyer_requests', 'quality_required')) {
                $table->enum('quality_required', ['A', 'B', 'C', 'any'])->default('any');
            }
            if (!Schema::hasColumn('buyer_requests', 'organic_only')) {
                $table->boolean('organic_only')->default(false);
            }
            
            // Delivery
            if (!Schema::hasColumn('buyer_requests', 'pickup_available')) {
                $table->boolean('pickup_available')->default(false);
            }
            if (!Schema::hasColumn('buyer_requests', 'delivery_required')) {
                $table->boolean('delivery_required')->default(true);
            }
            if (!Schema::hasColumn('buyer_requests', 'delivery_instructions')) {
                $table->text('delivery_instructions')->nullable();
            }
            
            // Budget
            if (!Schema::hasColumn('buyer_requests', 'min_price')) {
                $table->decimal('min_price', 10, 2)->nullable();
            }
            if (!Schema::hasColumn('buyer_requests', 'payment_terms')) {
                $table->enum('payment_terms', ['cash_on_delivery', 'advance', 'credit_30', 'credit_60'])->default('cash_on_delivery');
            }
            
            // Stats
            if (!Schema::hasColumn('buyer_requests', 'offers_received')) {
                $table->integer('offers_received')->default(0);
            }
            if (!Schema::hasColumn('buyer_requests', 'expires_at')) {
                $table->timestamp('expires_at')->nullable();
            }
        });
    }

    public function down()
    {
        Schema::table('buyer_requests', function (Blueprint $table) {
            $columns = [
                'unit', 'needed_by', 'urgency', 'quality_required', 'organic_only',
                'pickup_available', 'delivery_required', 'delivery_instructions',
                'min_price', 'payment_terms', 'offers_received', 'expires_at'
            ];
            foreach ($columns as $column) {
                if (Schema::hasColumn('buyer_requests', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
