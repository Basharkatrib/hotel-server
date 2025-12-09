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
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('reviewable_type'); // 'hotel' or 'room'
            $table->unsignedBigInteger('reviewable_id');
            $table->tinyInteger('rating')->unsigned(); // 1-5
            $table->string('title', 100)->nullable();
            $table->text('comment');
            $table->timestamps();

            // Indexes
            $table->index(['reviewable_type', 'reviewable_id'], 'idx_reviewable');
            $table->index('user_id', 'idx_user_id');
            $table->index('rating', 'idx_rating');
            
            // Unique constraint: one review per user per hotel/room
            $table->unique(['user_id', 'reviewable_type', 'reviewable_id'], 'unique_user_reviewable');
            
            // Note: Rating validation (1-5) is handled in StoreReviewRequest and UpdateReviewRequest
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
