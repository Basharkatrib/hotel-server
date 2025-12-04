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
        Schema::create('hotels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('address');
            $table->string('city')->nullable();
            $table->string('country')->default('Spain');
            
            // Location coordinates
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            
            // Pricing
            $table->decimal('price_per_night', 10, 2);
            $table->decimal('original_price', 10, 2)->nullable();
            $table->integer('discount_percentage')->default(0);
            
            // Hotel details
            $table->enum('type', ['hotel', 'room', 'entire_home'])->default('hotel');
            $table->decimal('rating', 2, 1)->default(0);
            $table->integer('reviews_count')->default(0);
            $table->string('room_type')->nullable(); // e.g., 'Sea View Room'
            $table->string('bed_type')->nullable(); // e.g., 'King Bed'
            $table->integer('room_size')->nullable(); // in m²
            $table->integer('available_rooms')->default(1);
            
            // Distances
            $table->decimal('distance_from_center', 8, 2)->nullable(); // in km
            $table->decimal('distance_from_beach', 8, 2)->nullable(); // in meters
            
            // Amenities (boolean)
            $table->boolean('has_metro_access')->default(false);
            $table->boolean('has_free_cancellation')->default(false);
            $table->boolean('has_spa_access')->default(false);
            $table->boolean('has_breakfast_included')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_getaway_deal')->default(false);
            
            // Images and amenities (JSON)
            $table->json('images')->nullable();
            $table->json('amenities')->nullable(); // ['WiFi', 'Pool', 'Parking']
            
            $table->timestamps();
            
            // Indexes
            $table->index(['city', 'type']);
            $table->index('rating');
            $table->index('price_per_night');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hotels');
    }
};
