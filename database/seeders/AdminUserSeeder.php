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
        // Check if admin already exists
        $existingAdmin = User::where('email', 'admin@gmail.com')->first();

        if ($existingAdmin) {
            $this->command->warn('Admin user already exists!');
            return;
        }

        // Create admin user
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('Admin123'),
            'email_verified_at' => now(),
        ]);

        $this->command->info('✅ Admin user created successfully!');
        $this->command->info('📧 Email: admin@gmail.com');
        $this->command->info('🔑 Password: Admin123');
    }
}
