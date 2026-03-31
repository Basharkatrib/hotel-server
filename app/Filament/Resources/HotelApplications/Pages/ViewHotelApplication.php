<?php

namespace App\Filament\Resources\HotelApplications\Pages;

use App\Filament\Resources\HotelApplications\HotelApplicationResource;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewHotelApplication extends ViewRecord
{
    protected static string $resource = HotelApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label('Approve Application')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Approve this application?')
                ->modalDescription('This will grant the applicant the Hotel Owner role immediately.')
                ->modalSubmitActionLabel('Yes, approve')
                ->visible(fn() => $this->record->status === 'pending')
                ->action(function () {
                    $this->record->update(['status' => 'approved']);
                    if ($this->record->user) {
                        $this->record->user->update(['role' => 'hotel_owner']);
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
                ->visible(fn() => $this->record->status === 'pending')
                ->form([
                    Textarea::make('rejection_reason')
                        ->label('Reason for rejection')
                        ->placeholder('Explain why this application is being rejected...')
                        ->required()
                        ->rows(4),
                ])
                ->action(function (array $data) {
                    $this->record->update([
                        'status'           => 'rejected',
                        'rejection_reason' => $data['rejection_reason'],
                    ]);
                    Notification::make()
                        ->title('Application rejected')
                        ->danger()
                        ->send();
                }),
        ];
    }
}