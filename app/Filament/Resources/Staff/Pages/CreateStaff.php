<?php

namespace App\Filament\Resources\Staff\Pages;

use App\Filament\Resources\Staff\StaffResource;
use Filament\Resources\Pages\CreateRecord;

class CreateStaff extends CreateRecord
{
    protected static string $resource = StaffResource::class;

    protected function afterCreate(): void
    {
        $staff = $this->record;
        $user = $staff->user;

        // If the user being added as staff is a regular user, upgrade their role
        if ($user->role === 'user') {
            $user->update(['role' => 'hotel_staff']);
        }
    }
}
