<?php

namespace App\Filament\Resources\Staff;

use App\Filament\Resources\Staff\Pages\CreateStaff;
use App\Filament\Resources\Staff\Pages\EditStaff;
use App\Filament\Resources\Staff\Pages\ListStaff;
use App\Filament\Resources\Staff\Schemas\StaffForm;
use App\Filament\Resources\Staff\Tables\StaffTable;
use App\Models\HotelStaff;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StaffResource extends Resource
{
    protected static ?string $model = HotelStaff::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|\UnitEnum|null $navigationGroup = 'Staff Management';

    protected static ?int $navigationSort = 3;

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        if ($user->isAdmin() || $user->isHotelOwner()) return true;

        if ($user->isHotelStaff()) {
            return $user->hotelStaff()->whereHas('permissions', fn($q) => $q->where('name', 'manage_staff'))->exists();
        }

        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user->isHotelOwner()) {
            return $query->whereHas('hotel', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        if ($user->isHotelStaff()) {
            $hotelIds = $user->hotelStaff()->pluck('hotel_id');
            return $query->whereIn('hotel_id', $hotelIds);
        }

        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        return StaffForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StaffTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStaff::route('/'),
            'create' => CreateStaff::route('/create'),
            'edit' => EditStaff::route('/{record}/edit'),
        ];
    }
}
