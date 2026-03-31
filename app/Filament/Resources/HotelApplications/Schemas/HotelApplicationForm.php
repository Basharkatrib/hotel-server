<?php

namespace App\Filament\Resources\HotelApplications\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class HotelApplicationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('user_id')
                    ->required()
                    ->numeric(),
                TextInput::make('hotel_name')
                    ->tel()
                    ->required(),
                TextInput::make('property_address')
                    ->required(),
                TextInput::make('property_type')
                    ->required(),
                TextInput::make('legal_name')
                    ->required(),
                TextInput::make('job_title')
                    ->required(),
                TextInput::make('contact_email')
                    ->email()
                    ->required(),
                TextInput::make('contact_phone')
                    ->tel()
                    ->required(),
                Select::make('status')
                    ->options(['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'])
                    ->default('pending')
                    ->required(),
                Textarea::make('rejection_reason')
                    ->columnSpanFull(),
            ]);
    }
}
