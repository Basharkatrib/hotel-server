<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use Filament\Widgets\ChartWidget;

class BookingStatusChart extends ChartWidget
{
    protected static ?int $sort = 4;

    public function getHeading(): string
    {
        return 'Booking Distribution';
    }

    protected function getData(): array
    {
        $user = auth()->user();
        $query = Booking::query();

        if ($user->isHotelOwner()) {
            $query->whereHas('hotel', fn ($q) => $q->where('user_id', $user->id));
        }

        $statuses = ['pending', 'confirmed', 'completed', 'cancelled'];
        $data = [];
        $labels = [];
        $colors = [
            'pending' => '#f59e0b', // Amber
            'confirmed' => '#3b82f6', // Blue
            'completed' => '#10b981', // Emerald
            'cancelled' => '#ef4444', // Red
        ];
        $bgColors = [];

        foreach ($statuses as $status) {
            $count = (clone $query)->where('status', $status)->count();
            $data[] = $count;
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
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
