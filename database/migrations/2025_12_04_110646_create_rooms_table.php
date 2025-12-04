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
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained('hotels')->onDelete('cascade');
            
            // Basic Information
            $table->string('name'); // e.g., 'Superior Twin Room'
            $table->text('description')->nullable();
            $table->enum('type', ['single', 'double', 'suite', 'deluxe', 'penthouse'])->default('double');
            
            // Room Size & Capacity
            $table->integer('size')->nullable(); // in m²
            $table->integer('max_guests')->default(2);
            
            // Beds Configuration
            $table->integer('single_beds')->default(0);
            $table->integer('double_beds')->default(0);
            $table->integer('king_beds')->default(0);
            $table->integer('queen_beds')->default(0);
            
            // Pricing
            $table->decimal('price_per_night', 10, 2);
            $table->decimal('original_price', 10, 2)->nullable();
            $table->integer('discount_percentage')->default(0);
            
            // Availability
            $table->boolean('is_available')->default(true);
            
            // Room Features (boolean)
            $table->boolean('has_breakfast')->default(false);
            $table->boolean('has_wifi')->default(true);
            $table->boolean('has_ac')->default(true);
            $table->boolean('has_tv')->default(true);
            $table->boolean('has_minibar')->default(false);
            $table->boolean('has_safe')->default(false);
            $table->boolean('has_balcony')->default(false);
            $table->boolean('has_bathtub')->default(false);
            $table->boolean('has_shower')->default(true);
            $table->boolean('no_smoking')->default(true);
            
            // Views
            $table->enum('view', ['city', 'sea', 'mountain', 'garden', 'pool', 'none'])->default('none');
            
            // Images
            $table->json('images')->nullable();
            
            // Rating
            $table->decimal('rating', 2, 1)->default(0);
            $table->integer('reviews_count')->default(0);
            
            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            
            $table->timestamps();
            
            // Indexes
            $table->index(['hotel_id', 'is_active']);
            $table->index('price_per_night');
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
