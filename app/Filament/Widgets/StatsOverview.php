<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use App\Models\Hotel;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class StatsOverview extends BaseWidget
{
    protected ?string $pollingInterval = null;
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $user = auth()->user();
        $hotelIds = $user->getHotelIds();

        $cacheKey = 'stats_overview_' . $user->id;
        $cacheTTL = 120; // Cache for 2 minutes

        return Cache::remember($cacheKey, $cacheTTL, function () use ($user, $hotelIds) {
            $stats = [];

            // Users Count (Admin only)
            if ($user->isAdmin()) {
                $stats[] = Stat::make('Total Users', User::count())
                    ->description('Total registered users')
                    ->descriptionIcon('heroicon-m-users')
                    ->color('info');
            }

            // Hotels Count
            $hotelsCount = $user->isAdmin()
                ? Hotel::count()
                : Hotel::whereIn('id', $hotelIds)->count();

            $stats[] = Stat::make('Total Hotels', $hotelsCount)
                ->description('Active hotels in system')
                ->descriptionIcon('heroicon-m-building-office')
                ->color('success');

            // Bookings Count
            $bookingsQuery = Booking::query();
            if (!$user->isAdmin()) {
                $bookingsQuery->whereIn('hotel_id', $hotelIds);
            }
            $stats[] = Stat::make('Total Bookings', $bookingsQuery->count())
                ->description('Total bookings made')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('primary');

            // Revenue
            $revenueQuery = Booking::query()->whereIn('status', ['confirmed', 'completed', 'pending']);
            if (!$user->isAdmin()) {
                $revenueQuery->whereIn('hotel_id', $hotelIds);
            }
            $revenue = $revenueQuery->sum('total_amount');

            $stats[] = Stat::make('Total Revenue', '$' . number_format($revenue, 2))
                ->description('Includes Pending, Confirmed & Completed')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success');

            return $stats;
        });
    }
}
