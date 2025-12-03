<?php

namespace App\Filament\Resources\Hotels\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class HotelsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('images')
                    ->label('Images')
                    ->badge()
                    ->formatStateUsing(function ($state) {
                        if (is_array($state)) {
                            $count = count($state);
                            return $count . ' ' . ($count === 1 ? 'image' : 'images');
                        }
                        return '0 images';
                    })
                    ->color('info')
                    ->icon('heroicon-o-photo')
                    ->toggleable(),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->limit(30),

                TextColumn::make('city')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-o-map-pin'),

                TextColumn::make('type')
                    ->badge()
                    ->colors([
                        'success' => 'hotel',
                        'warning' => 'room',
                        'info' => 'entire_home',
                    ]),

                TextColumn::make('price_per_night')
                    ->money('USD')
                    ->sortable()
                    ->label('Price')
                    ->weight('bold')
                    ->color('success'),

                TextColumn::make('discount_percentage')
                    ->suffix('%')
                    ->sortable()
                    ->badge()
                    ->color('danger')
                    ->visible(fn ($record) => $record && $record->discount_percentage > 0),

                TextColumn::make('rating')
                    ->sortable()
                    ->icon('heroicon-s-star')
                    ->iconColor('warning')
                    ->weight('semibold'),

                TextColumn::make('reviews_count')
                    ->numeric()
                    ->sortable()
                    ->label('Reviews')
                    ->suffix(' reviews')
                    ->color('gray'),

                TextColumn::make('available_rooms')
                    ->numeric()
                    ->sortable()
                    ->label('Available')
                    ->badge()
                    ->color(fn ($state) => $state && $state > 5 ? 'success' : ($state && $state > 0 ? 'warning' : 'danger')),

                IconColumn::make('is_featured')
                    ->boolean()
                    ->label('Featured')
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-star')
                    ->trueColor('warning')
                    ->falseColor('gray'),

                IconColumn::make('is_getaway_deal')
                    ->boolean()
                    ->label('Deal')
                    ->trueIcon('heroicon-o-tag')
                    ->falseIcon('heroicon-o-tag')
                    ->trueColor('success')
                    ->falseColor('gray'),

                IconColumn::make('has_free_cancellation')
                    ->boolean()
                    ->label('Free Cancel')
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('has_spa_access')
                    ->boolean()
                    ->label('Spa')
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('has_breakfast_included')
                    ->boolean()
                    ->label('Breakfast')
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('has_metro_access')
                    ->boolean()
                    ->label('Metro')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'hotel' => 'Hotel',
                        'room' => 'Room',
                        'entire_home' => 'Entire Home',
                    ]),

                SelectFilter::make('city')
                    ->options([
                        'Barcelona' => 'Barcelona',
                        'Madrid' => 'Madrid',
                        'Valencia' => 'Valencia',
                        'Seville' => 'Seville',
                        'Malaga' => 'Malaga',
                    ]),

                TernaryFilter::make('is_featured')
                    ->label('Featured Only')
                    ->placeholder('All hotels')
                    ->trueLabel('Featured hotels')
                    ->falseLabel('Not featured'),

                TernaryFilter::make('is_getaway_deal')
                    ->label('Getaway Deals')
                    ->placeholder('All hotels')
                    ->trueLabel('Deals only')
                    ->falseLabel('No deals'),

                TernaryFilter::make('has_free_cancellation')
                    ->label('Free Cancellation'),

                TernaryFilter::make('has_spa_access')
                    ->label('Spa Access'),

                TernaryFilter::make('has_breakfast_included')
                    ->label('Breakfast Included'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
