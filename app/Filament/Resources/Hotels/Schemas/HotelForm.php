<?php

namespace App\Filament\Resources\Hotels\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Afsakar\LeafletMapPicker\LeafletMapPicker;

class HotelForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Basic Information')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Hotel Arts Barcelona')
                            ->columnSpan(2),
                        
                        Select::make('type')
                            ->options([
                                'hotel' => 'Hotel',
                                'room' => 'Room',
                                'entire_home' => 'Entire Home'
                            ])
                            ->default('hotel')
                            ->required()
                            ->columnSpan(1),
                        
                        Textarea::make('description')
                            ->rows(4)
                            ->placeholder('Describe the hotel...')
                            ->columnSpanFull(),
                    ])
                    ->columns(3),

                Section::make('Location')
                    ->schema([
                        TextInput::make('address')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2),

                        TextInput::make('city')
                            ->columnSpan(1),

                        TextInput::make('country')
                            ->required()
                            ->default('Spain')
                            ->columnSpan(1),

                        LeafletMapPicker::make('location')
    ->label('Pick Location on Map')
    ->height('350px')
    ->defaultLocation([25.2048, 55.2708])
    ->defaultZoom(12)
    ->draggable()
    ->clickable()
    ->tileProvider('openstreetmap')
    ->myLocationButtonLabel('Use My Location')
    ->columnSpanFull()
    ->live()  // ← مهم جداً
    ->afterStateUpdated(function ($state, callable $set) {
        // جرب الـ formats المختلفة للـ package
        if (is_array($state)) {
            // format 1: [lat, lng]
            if (isset($state[0], $state[1])) {
                $set('latitude', $state[0]);
                $set('longitude', $state[1]);
            }
            // format 2: ['lat' => ..., 'lng' => ...]
            elseif (isset($state['lat'], $state['lng'])) {
                $set('latitude', $state['lat']);
                $set('longitude', $state['lng']);
            }
            // format 3: ['latitude' => ..., 'longitude' => ...]
            elseif (isset($state['latitude'], $state['longitude'])) {
                $set('latitude', $state['latitude']);
                $set('longitude', $state['longitude']);
            }
        }
    })
    ->afterStateHydrated(function (callable $set, $record) {
        if ($record?->latitude && $record?->longitude) {
            $set('location', [$record->latitude, $record->longitude]);
        }
    })
    ->extraAttributes([
        'style' => '
            --leaflet-marker-size: 0;
        '
    ]),

                        TextInput::make('latitude')
                            ->numeric()
                            ->step(0.00000001)
                            ->readOnly()
                            ->columnSpan(1),

                        TextInput::make('longitude')
                            ->numeric()
                            ->step(0.00000001)
                            ->readOnly()
                            ->columnSpan(1),

                        TextInput::make('distance_from_center')
                            ->numeric()
                            ->suffix('km')
                            ->placeholder('1.8')
                            ->columnSpan(1),
                        
                        TextInput::make('distance_from_beach')
                            ->numeric()
                            ->suffix('m')
                            ->placeholder('250')
                            ->columnSpan(1),

                        Toggle::make('has_metro_access')
                            ->label('Metro Access')
                            ->default(false)
                            ->inline(false)
                            ->columnSpan(1),
                    ])
                    ->columns(4),

                Section::make('Pricing')
                    ->schema([
                        TextInput::make('price_per_night')
                            ->required()
                            ->numeric()
                            ->prefix('$')
                            ->placeholder('150')
                            ->columnSpan(1),
                        
                        TextInput::make('original_price')
                            ->numeric()
                            ->prefix('$')
                            ->placeholder('200')
                            ->helperText('Leave empty if no discount')
                            ->columnSpan(1),
                        
                        TextInput::make('discount_percentage')
                            ->numeric()
                            ->suffix('%')
                            ->default(0)
                            ->minValue(0)
                            ->maxValue(100)
                            ->columnSpan(1),
                    ])
                    ->columns(3),

                Section::make('Room Details')
                    ->schema([
                        TextInput::make('room_type')
                            ->placeholder('Sea View Room')
                            ->columnSpan(1),
                        
                        TextInput::make('bed_type')
                            ->placeholder('King Bed')
                            ->columnSpan(1),
                        
                        TextInput::make('room_size')
                            ->numeric()
                            ->suffix('m²')
                            ->placeholder('40')
                            ->columnSpan(1),
                        
                        TextInput::make('available_rooms')
                            ->required()
                            ->numeric()
                            ->default(1)
                            ->minValue(0)
                            ->columnSpan(1),
                    ])
                    ->columns(4),

                Section::make('Rating & Reviews')
                    ->schema([
                        TextInput::make('rating')
                            ->numeric()
                            ->default(0.0)
                            ->step(0.1)
                            ->minValue(0)
                            ->maxValue(5)
                            ->placeholder('4.5')
                            ->columnSpan(1),
                        
                        TextInput::make('reviews_count')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->placeholder('1260')
                            ->columnSpan(1),
                    ])
                    ->columns(2),

                Section::make('Amenities & Features')
                    ->schema([
                        Toggle::make('has_free_cancellation')
                            ->label('Free Cancellation')
                            ->default(false)
                            ->inline(false),
                        
                        Toggle::make('has_spa_access')
                            ->label('Spa Access')
                            ->default(false)
                            ->inline(false),
                        
                        Toggle::make('has_breakfast_included')
                            ->label('Breakfast Included')
                            ->default(false)
                            ->inline(false),
                        
                        Toggle::make('is_featured')
                            ->label('Featured Hotel')
                            ->default(false)
                            ->inline(false),
                        
                        Toggle::make('is_getaway_deal')
                            ->label('Getaway Deal')
                            ->default(false)
                            ->inline(false),

                        TagsInput::make('amenities')
                            ->placeholder('Add amenity...')
                            ->suggestions([
                                'Free WiFi',
                                'Swimming Pool',
                                'Fitness Center',
                                'Restaurant',
                                'Bar',
                                'Parking',
                                'Air Conditioning',
                                '24/7 Reception',
                                'Room Service',
                                'Laundry Service',
                                'Airport Shuttle',
                                'Pet Friendly',
                            ])
                            ->columnSpanFull(),
                    ])
                    ->columns(5),

                Section::make('Images')
                    ->schema([
                        FileUpload::make('images')
                            ->label('Hotel Images')
                            ->image()
                            ->multiple()
                            ->reorderable()
                            ->maxFiles(10)
                            ->disk('public')
                            ->directory('hotels')
                            ->visibility('public')
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                '16:9',
                                '4:3',
                                '1:1',
                            ])
                            ->visibility('public')
                            ->columnSpanFull()
                            ->helperText('Upload up to 10 images. Drag to reorder.'),
                    ]),
            ]);
    }
}
