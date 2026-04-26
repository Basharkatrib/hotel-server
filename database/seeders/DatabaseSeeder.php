<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Call other seeders
        $this->call([
            PermissionSeeder::class,
            HotelSeeder::class,
            AdminUserSeeder::class,
            RoomSeeder::class,
        ]);
    }
}
