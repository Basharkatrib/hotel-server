<?php

namespace App\Filament\Resources\Advertisements\Schemas;

use App\Models\Hotel;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class AdvertisementForm
{
    public static function configure(Schema $schema): Schema
    {
        $user = auth()->user();

        return $schema->components([

            Select::make('hotel_id')
                ->label('Hotel')
                ->required()
                ->live()
                ->options(function () use ($user) {
                    // Admin يرى كل الفنادق، Owner يرى فنادقه فقط
                    if ($user->isAdmin()) {
                        return Hotel::pluck('name', 'id');
                    }
                    return Hotel::whereIn('id', $user->getHotelIds())->pluck('name', 'id');
                })
                ->searchable(),

            TextInput::make('title')
                ->label('Advertisement Title')
                ->required()
                ->maxLength(255),

            Textarea::make('description')
                ->label('Description')
                ->nullable()
                ->rows(3),

            Select::make('discount_type')
                ->label('Discount Type')
                ->options([
                    'percentage' => 'Percentage (%)',
                    'fixed'      => 'Fixed Amount',
                ])
                ->required()
                ->live(),

            TextInput::make('discount_value')
                ->label(fn (Get $get) =>
                    $get('discount_type') === 'percentage'
                        ? 'Discount Percentage (%)'
                        : 'Discount Amount'
                )
                ->numeric()
                ->minValue(0)
                ->required(),

            Select::make('applies_to')
                ->label('Applies To')
                ->options([
                    'all_rooms'      => 'All Rooms',
                    'specific_rooms' => 'Specific Rooms',
                ])
                ->live()
                ->required(),

            // يظهر فقط عند اختيار specific_rooms
            // ويُفلتر حسب الفندق المختار
            Select::make('rooms')
                ->relationship('rooms', 'name')
                ->multiple()
                ->preload()
                ->label('Select Rooms')
                ->visible(fn (Get $get) => $get('applies_to') === 'specific_rooms')
                ->options(function (Get $get) use ($user) {
                    $hotelId = $get('hotel_id');
                    if (!$hotelId) return [];

                    // تأكد أن الـ Owner يملك هذا الفندق
                    if (!$user->isAdmin() && !in_array($hotelId, $user->getHotelIds())) {
                        return [];
                    }

                    return \App\Models\Room::where('hotel_id', $hotelId)->pluck('name', 'id');
                }),

            DateTimePicker::make('starts_at')
                ->label('Start Date')
                ->required()
                ->native(false),

            DateTimePicker::make('ends_at')
                ->label('End Date')
                ->after('starts_at')
                ->required()
                ->native(false),

            Toggle::make('is_active')
                ->label('Active')
                ->default(true),
        ]);
    }
}