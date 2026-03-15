<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            ['name' => 'manage_bookings', 'label' => 'Manage Bookings'],
            ['name' => 'manage_rooms', 'label' => 'Manage Rooms'],
            ['name' => 'manage_staff', 'label' => 'Manage Staff'],
            ['name' => 'manage_hotel_info', 'label' => 'Manage Hotel Info'],
            ['name' => 'manage_entry_logs', 'label' => 'Manage Entry Logs'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission['name']], $permission);
        }
    }
}
