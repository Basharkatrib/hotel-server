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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            
            // Foreign Key
            $table->foreignId('booking_id')->constrained('bookings')->onDelete('cascade');
            
            // Stripe Information
            $table->string('stripe_payment_intent_id')->unique()->nullable();
            $table->string('stripe_charge_id')->nullable();
            
            // Payment Details
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('usd');
            $table->enum('status', ['pending', 'succeeded', 'failed', 'refunded'])->default('pending');
            
            // Payment Method
            $table->enum('payment_method', ['card', 'other'])->default('card');
            $table->string('card_last4', 4)->nullable();
            $table->string('card_brand', 20)->nullable();
            
            // Timestamps
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('booking_id');
            $table->index('stripe_payment_intent_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
