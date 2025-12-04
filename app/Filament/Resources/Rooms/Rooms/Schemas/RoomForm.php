<?php

namespace App\Filament\Resources\Rooms\Rooms\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RoomForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Basic Information')
                ->description('Enter the room\'s basic details')
                ->icon('heroicon-o-information-circle')
                ->columns(2)
                ->schema([
                    Select::make('hotel_id')
                        ->label('Hotel')
                        ->relationship('hotel', 'name')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->columnSpanFull(),

                    TextInput::make('name')
                        ->label('Room Name')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('e.g., Deluxe Sea View Room')
                        ->columnSpanFull(),

                    Textarea::make('description')
                        ->label('Description')
                        ->rows(4)
                        ->maxLength(1000)
                        ->placeholder('Describe the room features and amenities...')
                        ->columnSpanFull(),

                    Select::make('type')
                        ->label('Room Type')
                        ->options([
                            'single' => 'Single Room',
                            'double' => 'Double Room',
                            'suite' => 'Suite',
                            'deluxe' => 'Deluxe Room',
                            'penthouse' => 'Penthouse',
                        ])
                        ->required()
                        ->native(false),

                    Select::make('view')
                        ->label('View')
                        ->options([
                            'none' => 'No View',
                            'city' => 'City View',
                            'sea' => 'Sea View',
                            'mountain' => 'Mountain View',
                            'garden' => 'Garden View',
                            'pool' => 'Pool View',
                        ])
                        ->default('none')
                        ->native(false),
                ]),

            Section::make('Capacity & Beds')
                ->description('Room size and bed configuration')
                ->icon('heroicon-o-user-group')
                ->columns(3)
                ->schema([
                    TextInput::make('size')
                        ->label('Room Size (m²)')
                        ->numeric()
                        ->suffix('m²')
                        ->placeholder('35'),

                    TextInput::make('max_guests')
                        ->label('Max Guests')
                        ->numeric()
                        ->required()
                        ->default(2)
                        ->minValue(1)
                        ->maxValue(10)
                        ->suffix('persons')
                        ->columnSpan(3),

                    TextInput::make('single_beds')
                        ->label('Single Beds')
                        ->numeric()
                        ->default(0)
                        ->minValue(0)
                        ->suffix('beds'),

                    TextInput::make('double_beds')
                        ->label('Double Beds')
                        ->numeric()
                        ->default(0)
                        ->minValue(0)
                        ->suffix('beds'),

                    TextInput::make('queen_beds')
                        ->label('Queen Beds')
                        ->numeric()
                        ->default(0)
                        ->minValue(0)
                        ->suffix('beds'),

                    TextInput::make('king_beds')
                        ->label('King Beds')
                        ->numeric()
                        ->default(0)
                        ->minValue(0)
                        ->suffix('beds'),
                ]),

            Section::make('Pricing')
                ->description('Set room pricing and discounts')
                ->icon('heroicon-o-currency-dollar')
                ->columns(3)
                ->schema([
                    TextInput::make('price_per_night')
                        ->label('Price per Night')
                        ->numeric()
                        ->required()
                        ->prefix('$')
                        ->minValue(0)
                        ->step(0.01)
                        ->placeholder('150.00'),

                    TextInput::make('original_price')
                        ->label('Original Price (if discounted)')
                        ->numeric()
                        ->prefix('$')
                        ->minValue(0)
                        ->step(0.01)
                        ->placeholder('200.00')
                        ->helperText('Leave empty if no discount'),

                    TextInput::make('discount_percentage')
                        ->label('Discount %')
                        ->numeric()
                        ->default(0)
                        ->minValue(0)
                        ->maxValue(100)
                        ->suffix('%')
                        ->helperText('Auto-calculated or manual'),
                ]),

            Section::make('Room Features')
                ->description('Select available amenities')
                ->icon('heroicon-o-star')
                ->columns(4)
                ->schema([
                    Toggle::make('has_wifi')
                        ->label('Free WiFi')
                        ->default(true)
                        ->inline(false),

                    Toggle::make('has_ac')
                        ->label('Air Conditioner')
                        ->default(true)
                        ->inline(false),

                    Toggle::make('has_tv')
                        ->label('TV')
                        ->default(true)
                        ->inline(false),

                    Toggle::make('has_shower')
                        ->label('Shower')
                        ->default(true)
                        ->inline(false),

                    Toggle::make('has_breakfast')
                        ->label('Breakfast Included')
                        ->default(false)
                        ->inline(false),

                    Toggle::make('has_minibar')
                        ->label('Minibar')
                        ->default(false)
                        ->inline(false),

                    Toggle::make('has_safe')
                        ->label('Safe')
                        ->default(false)
                        ->inline(false),

                    Toggle::make('has_balcony')
                        ->label('Balcony')
                        ->default(false)
                        ->inline(false),

                    Toggle::make('has_bathtub')
                        ->label('Bathtub')
                        ->default(false)
                        ->inline(false),

                    Toggle::make('no_smoking')
                        ->label('No Smoking')
                        ->default(true)
                        ->inline(false),
                ]),

            Section::make('Images')
                ->description('Upload room images (multiple allowed)')
                ->icon('heroicon-o-photo')
                ->schema([
                    FileUpload::make('images')
                        ->label('Room Images')
                        ->image()
                        ->multiple()
                        ->reorderable()
                        ->maxFiles(10)
                        ->disk('public')
                        ->directory('rooms')
                        ->visibility('public')
                        ->imageEditor()
                        ->imageEditorAspectRatios([
                            '16:9',
                            '4:3',
                        ])
                        ->maxSize(2048)
                        ->helperText('Upload up to 10 images. Max size: 2MB per image.')
                        ->columnSpanFull(),
                ]),

            Section::make('Status & Rating')
                ->description('Room availability and ratings')
                ->icon('heroicon-o-check-circle')
                ->columns(3)
                ->schema([
                    Toggle::make('is_available')
                        ->label('Available for Booking')
                        ->default(true)
                        ->inline(false)
                        ->helperText('Turn off to hide from bookings'),

                    Toggle::make('is_active')
                        ->label('Active')
                        ->default(true)
                        ->inline(false)
                        ->helperText('Show/hide on website'),

                    Toggle::make('is_featured')
                        ->label('Featured Room')
                        ->default(false)
                        ->inline(false)
                        ->helperText('Highlight this room'),

                    TextInput::make('rating')
                        ->label('Rating')
                        ->numeric()
                        ->default(0)
                        ->minValue(0)
                        ->maxValue(5)
                        ->step(0.1)
                        ->suffix('/ 5')
                        ->placeholder('4.5'),

                    TextInput::make('reviews_count')
                        ->label('Reviews Count')
                        ->numeric()
                        ->default(0)
                        ->minValue(0)
                        ->suffix('reviews')
                        ->placeholder('150'),
                ]),
        ]);
    }
}
