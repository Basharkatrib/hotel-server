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

            // Hotels Count (Admin or Owner with multiple hotels)
            $hotelsCount = $user->isAdmin()
                ? Hotel::count()
                : Hotel::whereIn('id', $hotelIds)->count();

            if ($user->isAdmin() || $hotelsCount > 1) {
                $stats[] = Stat::make('Total Hotels', $hotelsCount)
                    ->description($user->isAdmin() ? 'Active hotels in system' : 'Your managed hotels')
                    ->descriptionIcon('heroicon-m-building-office')
                    ->color('success');
            }

            // Bookings Count
            $bookingsQuery = Booking::query();
            if (!$user->isAdmin()) {
                $bookingsQuery->whereIn('hotel_id', $hotelIds);
            }
            $stats[] = Stat::make('Total Bookings', $bookingsQuery->count())
                ->description($user->isAdmin() ? 'Total bookings made' : 'Bookings for your hotels')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('primary');

            // Revenue / Income
            $revenueQuery = Booking::query();
            if ($user->isAdmin()) {
                $revenueQuery->whereIn('status', ['confirmed', 'completed', 'pending']);
                $label = 'Total Revenue';
                $description = 'Includes Pending, Confirmed & Completed';
            } else {
                $revenueQuery->whereIn('hotel_id', $hotelIds)
                             ->whereIn('status', ['confirmed', 'completed']);
                $label = 'Total Income';
                $description = 'Confirmed and Completed bookings';
            }
            
            $revenue = $revenueQuery->sum('total_amount');

            $stats[] = Stat::make($label, '$' . number_format($revenue, 2))
                ->description($description)
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success');

            return $stats;
        });
    }
}
