<?php

namespace App\Filament\Resources\Bookings\Schemas;

use Filament\Schemas\Schema;

class BookingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make('Booking Details')
                    ->schema([
                        \Filament\Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->label('Guest')
                            ->disabled()
                            ->columnSpan(1),
                        
                        \Filament\Forms\Components\Select::make('hotel_id')
                            ->relationship('hotel', 'name')
                            ->label('Hotel')
                            ->disabled()
                            ->columnSpan(1),

                        \Filament\Forms\Components\Select::make('room_id')
                            ->relationship('room', 'name')
                            ->label('Room')
                            ->disabled()
                            ->columnSpan(1),

                        \Filament\Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'confirmed' => 'Confirmed',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled',
                            ])
                            ->required()
                            ->columnSpan(1),
                    ])
                    ->columns(2),

                \Filament\Schemas\Components\Section::make('Dates & Pricing')
                    ->schema([
                        \Filament\Forms\Components\DatePicker::make('check_in_date')
                            ->disabled(),
                        \Filament\Forms\Components\DatePicker::make('check_out_date')
                            ->disabled(),
                        \Filament\Forms\Components\TextInput::make('total_nights')
                            ->numeric()
                            ->disabled(),
                        \Filament\Forms\Components\TextInput::make('guests_count')
                            ->numeric()
                            ->label('Guests')
                            ->disabled(),
                        
                        \Filament\Forms\Components\TextInput::make('price_per_night')
                            ->numeric()
                            ->prefix('$')
                            ->disabled(),
                        \Filament\Forms\Components\TextInput::make('total_amount')
                            ->numeric()
                            ->prefix('$')
                            ->disabled(),
                    ])
                    ->columns(3),
                
                \Filament\Schemas\Components\Section::make('Guest Info')
                    ->schema([
                        \Filament\Forms\Components\TextInput::make('guest_name')
                            ->label('Guest Name (Manual)')
                            ->disabled(),
                        \Filament\Forms\Components\TextInput::make('guest_email')
                            ->label('Guest Email')
                            ->email()
                            ->disabled(),
                        \Filament\Forms\Components\TextInput::make('guest_phone')
                            ->label('Guest Phone')
                            ->tel()
                            ->disabled(),
                    ])
                    ->columns(3),
            ]);
    }
}
