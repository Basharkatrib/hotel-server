<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Rooms are now created in HotelSeeder for simplicity
        $this->command->info('Room logic has been integrated into HotelSeeder.');
    }
}
