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
        // 1. Create permissions table
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // e.g., 'manage_bookings', 'manage_rooms'
            $table->string('label'); // e.g., 'Manage Bookings'
            $table->timestamps();
        });

        // 2. Create hotel_staff table
        Schema::create('hotel_staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->string('position')->nullable(); // e.g., 'Receptionist', 'Manager'
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->unique(['user_id', 'hotel_id']);
        });

        // 3. Create hotel_staff_permissions pivot table
        Schema::create('hotel_staff_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_staff_id')->constrained('hotel_staff')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->timestamps();
            
            $table->unique(['hotel_staff_id', 'permission_id'], 'staff_permission_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hotel_staff_permissions');
        Schema::dropIfExists('hotel_staff');
        Schema::dropIfExists('permissions');
    }
};
