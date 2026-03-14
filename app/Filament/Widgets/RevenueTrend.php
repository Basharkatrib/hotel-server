<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;

class RevenueTrend extends ChartWidget
{
    protected static bool $isLazy = true;
    protected static ?int $sort = 4;
    protected ?string $pollingInterval = null;

    public function getHeading(): string
    {
        return 'Monthly Revenue';
    }

    protected function getData(): array
    {
        $user = auth()->user();
        $hotelIds = $user->getHotelIds();
        $cacheKey = 'revenue_trend_' . $user->id;

        return Cache::remember($cacheKey, 120, function () use ($user, $hotelIds) {
            $query = Booking::query()
                ->whereIn('status', ['confirmed', 'completed', 'pending'])
                ->whereYear('created_at', now()->year);

            if (!$user->isAdmin()) {
                $query->whereIn('hotel_id', $hotelIds);
            }

            $monthlyRevenue = $query
                ->selectRaw('MONTH(created_at) as month, SUM(total_amount) as revenue')
                ->groupBy('month')
                ->orderBy('month')
                ->pluck('revenue', 'month')
                ->toArray();

            $data = [];
            $labels = [];

            for ($i = 1; $i <= 12; $i++) {
                $data[] = (float) ($monthlyRevenue[$i] ?? 0);
                $labels[] = now()->month($i)->format('M');
            }

            return [
                'datasets' => [
                    [
                        'label' => 'Revenue ($)',
                        'data' => $data,
                        'borderColor' => '#10b981',
                        'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                        'fill' => 'start',
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
