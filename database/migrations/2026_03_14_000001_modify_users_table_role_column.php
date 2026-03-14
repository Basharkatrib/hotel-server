<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add hotel_staff to the role column. 
        // Since it's an enum, we might need to modify it. 
        // For MySQL, we can just redeclare the enum.
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('user')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Cannot easily revert to enum if there is 'hotel_staff' data, 
            // but we'll leave it as string or change back if needed.
        });
    }
};
