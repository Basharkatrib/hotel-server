<?php

namespace App\Filament\Resources\Hotels;

use App\Filament\Resources\Hotels\Pages\CreateHotel;
use App\Filament\Resources\Hotels\Pages\EditHotel;
use App\Filament\Resources\Hotels\Pages\ListHotels;
use App\Filament\Resources\Hotels\Schemas\HotelForm;
use App\Filament\Resources\Hotels\Tables\HotelsTable;
use App\Models\Hotel;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class HotelResource extends Resource
{
    protected static ?string $model = Hotel::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;
    
    protected static ?string $navigationLabel = 'Hotels';
    
    protected static ?string $pluralModelLabel = 'Hotels';
    
    protected static ?string $modelLabel = 'Hotel';

    protected static ?int $navigationSort = 2;
    
    public static function canViewAny(): bool
    {
        $user = auth()->user();
        if ($user->isAdmin() || $user->isHotelOwner()) return true;

        if ($user->isHotelStaff()) {
            return $user->hotelStaff()->whereHas('permissions', fn($q) => $q->where('name', 'manage_hotel_info'))->exists();
        }

        return false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()->isAdmin();
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();
        
        if ($user->isHotelOwner()) {
            return $query->where('user_id', $user->id);
        }

        if ($user->isHotelStaff()) {
            $hotelIds = $user->hotelStaff()->pluck('hotel_id');
            return $query->whereIn('id', $hotelIds);
        }

        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        return HotelForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HotelsTable::configure($table);
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
            'index' => ListHotels::route('/'),
            'create' => CreateHotel::route('/create'),
            'edit' => EditHotel::route('/{record}/edit'),
        ];
    }
}
