<?php

namespace App\Filament\Resources\EntryLogs;

use App\Filament\Resources\EntryLogs\Pages;
use App\Models\EntryLog;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Illuminate\Database\Eloquent\Builder;

class EntryLogResource extends Resource
{
    protected static ?string $model = EntryLog::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-finger-print';

    protected static string|\UnitEnum|null $navigationGroup = 'Attendance';
    
    protected static ?int $navigationSort = 10;

      public static function canViewAny(): bool
    {
        $user = auth()->user();
        if ($user->isAdmin() || $user->isHotelOwner()) return true;

        if ($user->isHotelStaff()) {
            return $user->hotelStaff()->whereHas('permissions', fn($q) => $q->where('name', 'manage_entry_logs'))->exists();
        }

        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\Select::make('hotel_id')
                    ->relationship('hotel', 'name')
                    ->required(),
                Forms\Components\Select::make('booking_id')
                    ->relationship('booking', 'guest_name')
                    ->required(),
                Forms\Components\Select::make('room_id')
                    ->relationship('room', 'name')
                    ->required(),
                Forms\Components\DateTimePicker::make('verified_at')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('verified_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Entry Time'),
                Tables\Columns\TextColumn::make('booking.guest_name')
                    ->searchable()
                    ->label('Guest Name'),
                Tables\Columns\TextColumn::make('hotel.name')
                    ->searchable()
                    ->sortable()
                    ->label('Hotel'),
                Tables\Columns\TextColumn::make('room.name')
                    ->searchable()
                    ->label('Room'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('hotel_id')
                    ->relationship('hotel', 'name')
                    ->label('Hotel')
                    ->visible(fn () => auth()->user()->isAdmin()),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('verified_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        
        if (!auth()->user()->isAdmin()) {
            $query->whereIn('hotel_id', auth()->user()->getHotelIds());
        }
        
        return $query;
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEntryLogs::route('/'),
        ];
    }
}
