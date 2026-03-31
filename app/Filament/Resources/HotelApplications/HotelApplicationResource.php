<?php

namespace App\Filament\Resources\HotelApplications;

use App\Filament\Resources\HotelApplications\Pages\CreateHotelApplication;
use App\Filament\Resources\HotelApplications\Pages\EditHotelApplication;
use App\Filament\Resources\HotelApplications\Pages\ListHotelApplications;
use App\Filament\Resources\HotelApplications\Pages\ViewHotelApplication;
use App\Models\HotelApplication;
use BackedEnum;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Support\Icons\Heroicon;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class HotelApplicationResource extends Resource
{
    protected static ?string $model = HotelApplication::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice;

    protected static ?string $navigationLabel = 'Partner Applications';

    protected static ?string $pluralModelLabel = 'Partner Applications';

    protected static ?string $modelLabel = 'Application';

    protected static string|\UnitEnum|null $navigationGroup = 'Hotel Management';

    protected static ?int $navigationSort = 5;

    protected static ?string $recordTitleAttribute = 'hotel_name';

    public static function canAccess(): bool
    {
        return auth()->user()?->role === 'admin';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([

            Section::make('Hotel Information')
                ->description('Details submitted by the applicant about their property.')
                ->icon('heroicon-o-building-office-2')
                ->schema([
                    TextEntry::make('hotel_name')
                        ->label('Hotel Name'),
                    TextEntry::make('property_type')
                        ->label('Property Type'),
                    TextEntry::make('property_address')
                        ->label('Address')
                        ->icon('heroicon-m-map-pin')
                        ->columnSpanFull(),
                ])->columns(2),

            Section::make('Legal Representative')
                ->description('The person legally responsible for this application.')
                ->icon('heroicon-o-user-circle')
                ->schema([
                    TextEntry::make('legal_name')
                        ->label('Full Name'),
                    TextEntry::make('job_title')
                        ->label('Job Title'),
                    TextEntry::make('contact_email')
                        ->label('Email')
                        ->icon('heroicon-m-envelope')
                        ->copyable()
                        ->copyMessage('Email copied!')
                        ->color('info'),
                    TextEntry::make('contact_phone')
                        ->label('Phone')
                        ->icon('heroicon-m-phone')
                        ->copyable()
                        ->copyMessage('Phone copied!'),
                ])->columns(2),

            Section::make('Application Status')
                ->icon('heroicon-o-clipboard-document-check')
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('status')
                        ->label('Current Status')
                        ->badge()
                        ->color(fn($state) => match($state) {
                            'approved' => 'success',
                            'rejected' => 'danger',
                            'pending'  => 'warning',
                            default    => 'gray',
                        })
                        ->icon(fn($state) => match($state) {
                            'approved' => 'heroicon-m-check-circle',
                            'rejected' => 'heroicon-m-x-circle',
                            'pending'  => 'heroicon-m-clock',
                            default    => null,
                        }),
                    TextEntry::make('created_at')
                        ->label('Submitted At')
                        ->dateTime('M j, Y · H:i')
                        ->icon('heroicon-m-calendar'),
                    TextEntry::make('rejection_reason')
                        ->label('Rejection Reason')
                        ->visible(fn($record) => $record?->status === 'rejected')
                        ->columnSpanFull()
                        ->color('danger')
                        ->icon('heroicon-m-exclamation-triangle'),
                ])->columns(2),

            Section::make('Uploaded Documents')
                ->description('Review all supporting documents before making a decision.')
                ->icon('heroicon-o-paper-clip')
                ->columnSpanFull()
                ->schema([
                    RepeatableEntry::make('documents')
                        ->schema([
                            TextEntry::make('type')
                                ->label('Document Type')
                                ->badge()
                                ->color('info'),
                            TextEntry::make('original_name')
                                ->label('File Name')
                                ->icon('heroicon-m-document'),
                            TextEntry::make('size')
                                ->label('Size')
                                ->formatStateUsing(fn($state) => number_format($state / 1024 / 1024, 2) . ' MB'),
                            TextEntry::make('disk_path')
                                ->label('Action')
                                ->formatStateUsing(fn() => 'Open File ↗')
                                ->url(fn($record) => asset('storage/' . $record->disk_path))
                                ->openUrlInNewTab()
                                ->color('info'),
                        ])->columns(4),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->striped()
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('hotel_name')
                    ->label('Hotel Name')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Medium),

                TextColumn::make('user.email')
                    ->label('Applicant Email')
                    ->searchable()
                    ->icon('heroicon-m-envelope')
                    ->color('gray'),

                TextColumn::make('legal_name')
                    ->label('Legal Representative'),

                TextColumn::make('contact_phone')
                    ->label('Phone')
                    ->icon('heroicon-m-phone'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn($state) => match($state) {
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'pending'  => 'warning',
                        default    => 'gray',
                    })
                    ->icon(fn($state) => match($state) {
                        'approved' => 'heroicon-m-check-circle',
                        'rejected' => 'heroicon-m-x-circle',
                        'pending'  => 'heroicon-m-clock',
                        default    => null,
                    }),

                TextColumn::make('created_at')
                    ->label('Submitted At')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->color('gray'),
            ])
            ->filters([])
            ->actions([
                Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->url(fn($record) => static::getUrl('view', ['record' => $record])),

                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Application')
                    ->modalDescription('This will grant the user the Hotel Owner role immediately.')
                    ->modalSubmitActionLabel('Yes, approve')
                    ->visible(fn($record) => $record->status === 'pending')
                    ->action(function ($record) {
                        $record->update(['status' => 'approved']);
                        if ($record->user) {
                            $record->user->update(['role' => 'hotel_owner']);
                        }
                        Notification::make()
                            ->title('Application approved successfully')
                            ->success()
                            ->send();
                    }),

                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn($record) => $record->status === 'pending')
                    ->form([
                        Textarea::make('rejection_reason')
                            ->label('Rejection Reason')
                            ->placeholder('Explain why this application is being rejected...')
                            ->required()
                            ->rows(4),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'status'           => 'rejected',
                            'rejection_reason' => $data['rejection_reason'],
                        ]);
                        Notification::make()
                            ->title('Application rejected')
                            ->danger()
                            ->send();
                    }),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListHotelApplications::route('/'),
            'view'   => ViewHotelApplication::route('/{record}'),
            'create' => CreateHotelApplication::route('/create'),
            'edit'   => EditHotelApplication::route('/{record}/edit'),
        ];
    }
}