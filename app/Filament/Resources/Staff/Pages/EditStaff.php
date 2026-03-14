<?php

namespace App\Filament\Resources\Staff\Pages;

use App\Filament\Resources\Staff\StaffResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Hash;

class EditStaff extends EditRecord
{
    protected static string $resource = StaffResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Load user data into the form fields
        $user = $this->record->user;
        $data['staff_name'] = $user->name;
        $data['staff_email'] = $user->email;

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Update user account when saving staff record
        $user = $this->record->user;
        
        $userData = [
            'name' => $data['staff_name'],
            'email' => $data['staff_email'],
        ];

        if (filled($data['staff_password'])) {
            $userData['password'] = Hash::make($data['staff_password']);
        }

        $user->update($userData);

        return $data;
    }
}
