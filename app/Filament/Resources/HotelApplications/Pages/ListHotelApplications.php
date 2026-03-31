<?php

namespace App\Filament\Resources\HotelApplications\Pages;

use App\Filament\Resources\HotelApplications\HotelApplicationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHotelApplications extends ListRecords
{
    protected static string $resource = HotelApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
