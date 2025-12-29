<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Admin User
        $admin = User::firstOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('Admin123'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        if ($admin->wasRecentlyCreated) {
            $this->command->info('✅ Admin user created successfully!');
        } else {
            $this->command->warn('Admin user already exists.');
        }

        // 2. Hotel Owner
        $owner = User::firstOrCreate(
            ['email' => 'owner@gmail.com'],
            [
                'name' => 'Hotel Owner',
                'password' => Hash::make('Owner123'),
                'role' => 'hotel_owner',
                'email_verified_at' => now(),
            ]
        );

        if ($owner->wasRecentlyCreated) {
            $this->command->info('✅ Hotel Owner created successfully!');
            $this->command->info('📧 Email: owner@gmail.com');
            $this->command->info('🔑 Password: Owner123');
        } else {
            $this->command->warn('Hotel Owner already exists.');
        }

        // 3. Regular User
        $user = User::firstOrCreate(
            ['email' => 'user@gmail.com'],
            [
                'name' => 'Regular User',
                'password' => Hash::make('User123'),
                'role' => 'user',
                'email_verified_at' => now(),
            ]
        );

        if ($user->wasRecentlyCreated) {
            $this->command->info('✅ Regular User created successfully!');
            $this->command->info('📧 Email: user@gmail.com');
            $this->command->info('🔑 Password: User123');
        } else {
            $this->command->warn('Regular User already exists.');
        }
    }
}
