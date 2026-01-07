<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class RevenueTrend extends ChartWidget
{
    protected static ?int $sort = 3;

    public function getHeading(): string
    {
        return 'Monthly Revenue';
    }

    protected function getData(): array
    {
        $user = auth()->user();
        $query = Booking::query()->whereIn('status', ['confirmed', 'completed', 'pending']);

        if ($user->isHotelOwner()) {
            $query->whereHas('hotel', fn ($q) => $q->where('user_id', $user->id));
        }

        $data = [];
        $labels = [];

        for ($i = 1; $i <= 12; $i++) {
            $month = now()->month($i)->format('M');
            $revenue = (clone $query)->whereMonth('created_at', $i)
                ->whereYear('created_at', now()->year)
                ->sum('total_amount');
            
            $data[] = (float) $revenue;
            $labels[] = $month;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Revenue ($)',
                    'data' => $data,
                    'borderColor' => '#10b981', // Emerald/Green
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'fill' => 'start',
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
