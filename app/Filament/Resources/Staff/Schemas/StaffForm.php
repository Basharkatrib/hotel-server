<?php

namespace App\Filament\Resources\Staff\Schemas;

use App\Models\Hotel;
use App\Models\Permission;
use App\Models\User;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class StaffForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Staff Information')
                ->columns(2)
                ->schema([
                    TextInput::make('staff_name')
                        ->label('Staff Name')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('Full Name'),

                    TextInput::make('staff_email')
                        ->label('Staff Email')
                        ->email()
                        ->required()
                        ->unique('users', 'email', ignoreRecord: true, modifyRuleUsing: function ($rule, $record) {
                            return $record ? $rule->ignore($record->user_id) : $rule;
                        })
                        ->placeholder('email@example.com'),

                    TextInput::make('staff_password')
                        ->label('Password')
                        ->password()
                        ->required(fn (string $operation): bool => $operation === 'create')
                        ->dehydrated(fn ($state) => filled($state))
                        ->placeholder('Password'),

                    Select::make('hotel_id')
                        ->label('Hotel')
                        ->options(function () {
                            if (auth()->user()->isAdmin()) {
                                return Hotel::pluck('name', 'id');
                            }
                            return auth()->user()->hotels()->pluck('name', 'id');
                        })
                        ->required()
                        ->helperText('Assign this staff member to a hotel.'),

                    TextInput::make('position')
                        ->label('Job Position')
                        ->placeholder('Receptionist, Manager, etc.')
                        ->maxLength(255),

                    Toggle::make('is_active')
                        ->label('Active Status')
                        ->default(true),
                ]),

            Section::make('Permissions')
                ->description('Select the actions this staff member is allowed to perform.')
                ->schema([
                    CheckboxList::make('permissions')
                        ->relationship('permissions', 'label')
                        ->columns(2)
                        ->gridDirection('vertical')
                        ->required()
                        ->helperText('Assign specific permissions for this hotel.'),
                ]),
        ]);
    }
}
