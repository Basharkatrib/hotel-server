<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use App\Models\Hotel;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $user = auth()->user();
        $stats = [];

        // Users Count (Admin only)
        if ($user->isAdmin()) {
            $stats[] = Stat::make('Total Users', User::count())
                ->description('Total registered users')
                ->descriptionIcon('heroicon-m-users')
                ->color('info');
        }

        // Hotels Count
        $hotelsQuery = Hotel::query();
        if ($user->isHotelOwner()) {
            $hotelsQuery->where('user_id', $user->id);
        }
        $stats[] = Stat::make('Total Hotels', $hotelsQuery->count())
            ->description('Active hotels in system')
            ->descriptionIcon('heroicon-m-building-office')
            ->color('success');

        // Bookings Count
        $bookingsQuery = Booking::query();
        if ($user->isHotelOwner()) {
            $bookingsQuery->whereHas('hotel', fn ($query) => $query->where('user_id', $user->id));
        }
        $stats[] = Stat::make('Total Bookings', $bookingsQuery->count())
            ->description('Total bookings made')
            ->descriptionIcon('heroicon-m-calendar-days')
            ->color('primary');

        // Revenue
        $revenueQuery = Booking::query()->whereIn('status', ['confirmed', 'completed', 'pending']);
        if ($user->isHotelOwner()) {
            $revenueQuery->whereHas('hotel', fn ($query) => $query->where('user_id', $user->id));
        }
        $revenue = $revenueQuery->sum('total_amount');
        
        $stats[] = Stat::make('Total Revenue', '$' . number_format($revenue, 2))
            ->description('Includes Pending, Confirmed & Completed')
            ->descriptionIcon('heroicon-m-currency-dollar')
            ->color('success');

        return $stats;
    }
}
