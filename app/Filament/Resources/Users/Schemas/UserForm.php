<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required(),
                DateTimePicker::make('email_verified_at'),
                TextInput::make('password')
                    ->password()
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $operation): bool => $operation === 'create'),
                Select::make('role')
                    ->options([
                        'admin' => 'Admin',
                        'hotel_owner' => 'Hotel Owner',
                        'user' => 'Regular User',
                    ])
                    ->required()
                    ->native(false)
                    ->live(),
                Select::make('hotels')
                    ->relationship('hotels', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->visible(fn (Get $get) => $get('role') === 'hotel_owner')
                    ->label('Assigned Hotels'),
            ]);
    }
}
