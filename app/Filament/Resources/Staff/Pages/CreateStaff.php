<?php

namespace App\Filament\Resources\Staff\Pages;

use App\Filament\Resources\Staff\StaffResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;

class CreateStaff extends CreateRecord
{
    protected static string $resource = StaffResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // 1. Create the user account with the staff role
        $user = User::create([
            'name' => $data['staff_name'],
            'email' => $data['staff_email'],
            'password' => Hash::make($data['staff_password']),
            'role' => 'hotel_staff',
        ]);

        // 2. Link the new user's ID to the staff record
        $data['user_id'] = $user->id;

        return $data;
    }
}
