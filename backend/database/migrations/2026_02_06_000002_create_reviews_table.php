<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deal_id')->constrained()->onDelete('cascade');
            $table->foreignId('reviewer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('reviewee_id')->constrained('users')->onDelete('cascade');
            
            $table->tinyInteger('rating')->unsigned(); // 1-5
            $table->enum('review_type', ['quality', 'delivery', 'communication', 'overall'])->default('overall');
            $table->text('comment')->nullable();
            
            $table->boolean('is_verified_purchase')->default(true);
            
            $table->timestamps();
            
            $table->index(['reviewee_id', 'rating']);
            $table->unique(['deal_id', 'reviewer_id']); // One review per deal per user
        });
        
        // Add rating columns to users table
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('average_rating', 3, 2)->default(0)->after('phone');
            $table->integer('total_reviews')->default(0)->after('average_rating');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['average_rating', 'total_reviews']);
        });
        Schema::dropIfExists('reviews');
    }
};
