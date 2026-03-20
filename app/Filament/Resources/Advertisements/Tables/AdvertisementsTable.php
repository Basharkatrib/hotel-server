<?php

namespace App\Filament\Resources\Advertisements\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;

class AdvertisementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('hotel.name')
                    ->label('Hotel')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('title')
                    ->label('Title')
                    ->searchable(),

                TextColumn::make('discount_value')
                    ->label('Discount')
                    ->formatStateUsing(fn ($state, $record) =>
                        $record->discount_type === 'percentage'
                            ? "{$state}%"
                            : "\${$state}"
                    ),

                TextColumn::make('applies_to')
                    ->label('Applies To')
                    ->formatStateUsing(fn ($state) =>
                        $state === 'all_rooms' ? 'All Rooms' : 'Specific Rooms'
                    ),

                TextColumn::make('starts_at')
                    ->dateTime()
                    ->label('Start Date')
                    ->sortable(),

                TextColumn::make('ends_at')
                    ->dateTime()
                    ->label('End Date')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(fn ($record) => match(true) {
                        !$record->is_active        => 'Disabled',
                        now() < $record->starts_at => 'Upcoming',
                        now() > $record->ends_at   => 'Expired',
                        default                    => 'Active',
                    })
                    ->color(fn (string $state): string => match($state) {
                        'Disabled' => 'danger',
                        'Upcoming' => 'warning',
                        'Expired'  => 'gray',
                        'Active'   => 'success',
                        default    => 'gray',
                    }),

                ToggleColumn::make('is_active')
                    ->label('Enable'),
            ])
            ->filters([
                Filter::make('active_now')
                    ->label('Active Now')
                    ->query(fn ($query) => $query->active()),

                Filter::make('upcoming')
                    ->label('Upcoming')
                    ->query(fn ($query) => $query
                        ->where('is_active', true)
                        ->where('starts_at', '>', now())
                    ),

                Filter::make('expired')
                    ->label('Expired')
                    ->query(fn ($query) => $query
                        ->where('ends_at', '<', now())
                    ),
            ])
            ->defaultSort('created_at', 'desc');
    }
}