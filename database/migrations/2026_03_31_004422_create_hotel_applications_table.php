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
        Schema::create('hotel_applications', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('hotel_name');
    $table->string('property_address');
    $table->string('property_type');
    $table->string('legal_name');
    $table->string('job_title');
    $table->string('contact_email');
    $table->string('contact_phone');
    $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
    $table->text('rejection_reason')->nullable();
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hotel_applications');
    }
};
