<?php

namespace Database\Seeders;

use App\Models\Hotel;
use App\Models\Room;
use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all hotels
        $hotels = Hotel::all();

        if ($hotels->isEmpty()) {
            $this->command->warn('No hotels found. Please run HotelSeeder first.');
            return;
        }

        $this->command->info('Creating rooms for each hotel...');

        foreach ($hotels as $hotel) {
            // Each hotel gets 3-6 different room types
            $roomCount = rand(3, 6);
            
            Room::factory($roomCount)->create([
                'hotel_id' => $hotel->id,
            ]);

            $this->command->info("Created {$roomCount} rooms for hotel: {$hotel->name}");
        }

        $totalRooms = Room::count();
        $this->command->info("✅ Total rooms created: {$totalRooms}");
    }
}
