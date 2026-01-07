<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class BookingsChart extends ChartWidget
{
    public function getHeading(): string
    {
        return 'Monthly Bookings';
    }

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $user = auth()->user();
        $query = Booking::query();

        if ($user->isHotelOwner()) {
            $query->whereHas('hotel', fn ($q) => $q->where('user_id', $user->id));
        }

        // Fallback manually if Trend package is not installed
        // But assuming standard Filament chart logic:
        $data = [];
        $labels = [];

        for ($i = 1; $i <= 12; $i++) {
            $month = now()->month($i)->format('M');
            $count = (clone $query)->whereMonth('created_at', $i)->whereYear('created_at', now()->year)->count();
            
            $data[] = $count;
            $labels[] = $month;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Bookings',
                    'data' => $data,
                    'fill' => 'start',
                    'borderColor' => '#3b82f6', // Blue color
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
