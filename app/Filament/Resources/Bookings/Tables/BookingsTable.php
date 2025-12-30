<?php

namespace App\Filament\Resources\Bookings\Tables;

use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class BookingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (\Illuminate\Database\Eloquent\Builder $query) => 
                $query->with(['user', 'hotel', 'room'])
            )
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),
                
                TextColumn::make('user.name')
                    ->label('Guest')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->user?->email ?? null)
                    ->default('N/A'),
                
                TextColumn::make('hotel.name')
                    ->label('Hotel')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->hotel?->city ?? null)
                    ->wrap()
                    ->limit(30)
                    ->default('N/A'),
                
                TextColumn::make('room.name')
                    ->label('Room')
                    ->searchable()
                    ->default('N/A')
                    ->formatStateUsing(fn ($state, $record) => $record->room?->name ?? 'N/A')
                    ->wrap()
                    ->limit(25),
                
                TextColumn::make('check_in_date')
                    ->label('Check In')
                    ->date('Y-m-d')
                    ->sortable(),
                
                TextColumn::make('check_out_date')
                    ->label('Check Out')
                    ->date('Y-m-d')
                    ->sortable(),
                
                TextColumn::make('total_nights')
                    ->label('Nights')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => ($state ?? 0) . ' night' . (($state ?? 0) != 1 ? 's' : '')),
                
                TextColumn::make('guests_count')
                    ->label('Guests')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => ($state ?? 0) . ' guest' . (($state ?? 0) != 1 ? 's' : '')),
                
                TextColumn::make('total_amount')
                    ->label('Total Amount')
                    ->money('USD')
                    ->sortable()
                    ->alignEnd(),
                
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state): string => match ($state) {
                        'pending' => 'warning',
                        'confirmed' => 'success',
                        'completed' => 'info',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state): string => match ($state) {
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                        default => $state ?? 'Unknown',
                    }),
                
                TextColumn::make('created_at')
                    ->label('Booking Date')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),
                
                Filter::make('check_in_date')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('check_in_from')
                            ->label('From Date'),
                        \Filament\Forms\Components\DatePicker::make('check_in_until')
                            ->label('Until Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['check_in_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('check_in_date', '>=', $date),
                            )
                            ->when(
                                $data['check_in_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('check_in_date', '<=', $date),
                            );
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
