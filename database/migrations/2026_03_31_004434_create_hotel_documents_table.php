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
        Schema::create('hotel_documents', function (Blueprint $table) {
    $table->id();
    $table->foreignId('application_id')->constrained('hotel_applications')->cascadeOnDelete();
    $table->enum('type', ['business_license', 'vat_certificate', 'insurance_certificate', 'representative_id']);
    $table->string('original_name');
    $table->string('disk_path');
    $table->string('mime_type');
    $table->unsignedBigInteger('size');
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hotel_documents');
    }
};
