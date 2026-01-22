<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;

class PopularRoomsChart extends ChartWidget
{
    protected ?string $heading = 'Popular Rooms Chart';

    protected function getData(): array
    {
        return [
            //
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
