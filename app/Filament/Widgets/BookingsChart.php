<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;

class BookingsChart extends ChartWidget
{
    protected static bool $isLazy = true;
    protected static ?int $sort = 5;
    protected ?string $pollingInterval = null;

    public function getHeading(): string
    {
        return 'Monthly Bookings';
    }

    protected function getData(): array
    {
        $user = auth()->user();
        $hotelIds = $user->getHotelIds();
        $cacheKey = 'bookings_chart_' . $user->id;

        return Cache::remember($cacheKey, 120, function () use ($user, $hotelIds) {
            $query = Booking::query()
                ->whereYear('created_at', now()->year);

            if (!$user->isAdmin()) {
                $query->whereIn('hotel_id', $hotelIds);
            }

            $monthlyData = $query
                ->selectRaw('MONTH(created_at) as month, COUNT(*) as count')
                ->groupBy('month')
                ->orderBy('month')
                ->pluck('count', 'month')
                ->toArray();

            $data = [];
            $labels = [];

            for ($i = 1; $i <= 12; $i++) {
                $data[] = $monthlyData[$i] ?? 0;
                $labels[] = now()->month($i)->format('M');
            }

            return [
                'datasets' => [
                    [
                        'label' => 'Bookings',
                        'data' => $data,
                        'fill' => 'start',
                        'borderColor' => '#3b82f6',
                        'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    ],
                ],
                'labels' => $labels,
            ];
        });
    }

    protected function getType(): string
    {
        return 'line';
    }
}
