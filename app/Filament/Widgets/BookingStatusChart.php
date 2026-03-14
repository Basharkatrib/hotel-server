<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;

class BookingStatusChart extends ChartWidget
{
    protected static bool $isLazy = true;
    protected static ?int $sort = 3;

    protected ?string $pollingInterval = null;

    public function getHeading(): string
    {
        return 'Booking Distribution';
    }

    protected function getData(): array
    {
        $user = auth()->user();
        $hotelIds = $user->getHotelIds();
        $cacheKey = 'booking_status_chart_' . $user->id;

        return Cache::remember($cacheKey, 120, function () use ($user, $hotelIds) {
            $query = Booking::query();

            if (!$user->isAdmin()) {
                $query->whereIn('hotel_id', $hotelIds);
            }

            $statusCounts = $query
                ->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();

            $statuses = ['pending', 'confirmed', 'completed', 'cancelled'];
            $data = [];
            $labels = [];
            $colors = [
                'pending' => '#f59e0b',
                'confirmed' => '#3b82f6',
                'completed' => '#10b981',
                'cancelled' => '#ef4444',
            ];
            $bgColors = [];

            foreach ($statuses as $status) {
                $data[] = $statusCounts[$status] ?? 0;
                $labels[] = ucfirst($status);
                $bgColors[] = $colors[$status];
            }

            return [
                'datasets' => [
                    [
                        'label' => 'Bookings',
                        'data' => $data,
                        'backgroundColor' => $bgColors,
                    ],
                ],
                'labels' => $labels,
            ];
        });
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
