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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            
            // Foreign Keys
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('room_id')->constrained('rooms')->onDelete('cascade');
            $table->foreignId('hotel_id')->constrained('hotels')->onDelete('cascade');
            
            // Booking Dates
            $table->date('check_in_date');
            $table->date('check_out_date');
            $table->integer('total_nights');
            
            // Guest Information
            $table->string('guest_name');
            $table->string('guest_email');
            $table->string('guest_phone');
            $table->integer('guests_count')->default(1);
            $table->integer('rooms_count')->default(1);
            $table->json('guests_details')->nullable(); // Store all guests information
            
            // Pricing Details
            $table->decimal('price_per_night', 10, 2);
            $table->decimal('subtotal', 10, 2);
            $table->decimal('service_fee', 10, 2)->default(0);
            $table->decimal('taxes', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            
            // Status
            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'completed'])->default('pending');
            
            // Additional Information
            $table->text('special_requests')->nullable();
            
            // Timestamps
            $table->timestamps();
            
            // Indexes
            $table->index(['user_id', 'status']);
            $table->index(['room_id', 'check_in_date', 'check_out_date']);
            $table->index(['hotel_id', 'status']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
