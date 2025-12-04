<?php

namespace App\Filament\Resources\Rooms\Rooms\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class RoomsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('hotel.name')
                    ->label('Hotel')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->limit(25)
                    ->weight('medium'),

                TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'single' => 'gray',
                        'double' => 'info',
                        'suite' => 'success',
                        'deluxe' => 'warning',
                        'penthouse' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('total_beds')
                    ->label('Beds')
                    ->getStateUsing(fn ($record) => 
                        $record->single_beds + $record->double_beds + 
                        $record->king_beds + $record->queen_beds
                    )
                    ->badge()
                    ->color('info'),

                TextColumn::make('max_guests')
                    ->label('Guests')
                    ->badge()
                    ->icon('heroicon-o-user-group')
                    ->sortable(),

                TextColumn::make('price_per_night')
                    ->label('Price')
                    ->money('USD')
                    ->sortable()
                    ->weight('bold')
                    ->color('success'),

                IconColumn::make('is_available')
                    ->label('Available')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),

                IconColumn::make('has_breakfast')
                    ->label('Breakfast')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->toggleable(),

                IconColumn::make('has_wifi')
                    ->label('WiFi')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->toggleable(),

                TextColumn::make('view')
                    ->badge()
                    ->toggleable(),

                TextColumn::make('rating')
                    ->label('Rating')
                    ->icon('heroicon-o-star')
                    ->sortable()
                    ->toggleable(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                IconColumn::make('is_featured')
                    ->label('Featured')
                    ->boolean()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('hotel_id')
                    ->label('Hotel')
                    ->relationship('hotel', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('type')
                    ->options([
                        'single' => 'Single',
                        'double' => 'Double',
                        'suite' => 'Suite',
                        'deluxe' => 'Deluxe',
                        'penthouse' => 'Penthouse',
                    ]),

                SelectFilter::make('view')
                    ->options([
                        'city' => 'City View',
                        'sea' => 'Sea View',
                        'mountain' => 'Mountain View',
                        'garden' => 'Garden View',
                        'pool' => 'Pool View',
                        'none' => 'No View',
                    ]),

                TernaryFilter::make('has_breakfast')
                    ->label('Breakfast Included'),

                TernaryFilter::make('has_wifi')
                    ->label('Free WiFi'),

                TernaryFilter::make('is_available')
                    ->label('Available for Booking'),

                TernaryFilter::make('is_active')
                    ->label('Active Rooms'),

                TernaryFilter::make('is_featured')
                    ->label('Featured Rooms'),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
