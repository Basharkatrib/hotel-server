<?php

namespace App\Filament\Resources\Rooms\Rooms;

use App\Filament\Resources\Rooms\Rooms\Pages\CreateRoom;
use App\Filament\Resources\Rooms\Rooms\Pages\EditRoom;
use App\Filament\Resources\Rooms\Rooms\Pages\ListRooms;
use App\Filament\Resources\Rooms\Rooms\Schemas\RoomForm;
use App\Filament\Resources\Rooms\Rooms\Tables\RoomsTable;
use App\Models\Room;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class RoomResource extends Resource
{
    protected static ?string $model = Room::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    
    protected static ?string $slug = 'rooms';
    
    protected static ?string $navigationLabel = 'Rooms';
    
    protected static ?string $pluralModelLabel = 'Rooms';
    
    protected static ?string $modelLabel = 'Room';

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();
        
        if (auth()->user()->isHotelOwner()) {
            return $query->whereHas('hotel', function ($q) {
                $q->where('user_id', auth()->id());
            });
        }

        return $query;
    }

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return RoomForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RoomsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRooms::route('/'),
            'create' => CreateRoom::route('/create'),
            'edit' => EditRoom::route('/{record}/edit'),
        ];
    }
}
