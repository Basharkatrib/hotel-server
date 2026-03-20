<?php

namespace App\Filament\Resources\Advertisements;

use App\Filament\Resources\Advertisements\Pages\CreateAdvertisement;
use App\Filament\Resources\Advertisements\Pages\EditAdvertisement;
use App\Filament\Resources\Advertisements\Pages\ListAdvertisements;
use App\Filament\Resources\Advertisements\Schemas\AdvertisementForm;
use App\Filament\Resources\Advertisements\Tables\AdvertisementsTable;
use App\Models\Advertisement;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AdvertisementResource extends Resource
{
    protected static ?string $model = Advertisement::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static ?string $navigationLabel = 'Advertisements';

    protected static ?string $modelLabel = 'Advertisement';

    protected static ?string $pluralModelLabel = 'Advertisements';

    protected static string|\UnitEnum|null $navigationGroup = 'Hotel Management';

    protected static ?int $navigationSort = 5;

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return $user->isAdmin() || $user->isHotelOwner();
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user->isAdmin()) {
            return $query;
        }

        // Hotel Owner يرى إعلانات فنادقه فقط
        return $query->whereIn('hotel_id', $user->getHotelIds());
    }

    public static function form(Schema $schema): Schema
    {
        return AdvertisementForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AdvertisementsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListAdvertisements::route('/'),
            'create' => CreateAdvertisement::route('/create'),
            'edit'   => EditAdvertisement::route('/{record}/edit'),
        ];
    }
}