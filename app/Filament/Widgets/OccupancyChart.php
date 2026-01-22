<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;

class OccupancyChart extends ChartWidget
{
    protected ?string $heading = 'Occupancy Chart';

    protected function getData(): array
    {
        return [
            //
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }
}
