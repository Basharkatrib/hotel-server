<?php

namespace App\Filament\Resources\HotelApplications\Pages;

use App\Filament\Resources\HotelApplications\HotelApplicationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditHotelApplication extends EditRecord
{
    protected static string $resource = HotelApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
