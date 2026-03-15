<?php

namespace App\Filament\Resources\EntryLogs\Pages;

use App\Filament\Resources\EntryLogs\EntryLogResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEntryLogs extends ListRecords
{
    protected static string $resource = EntryLogResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
