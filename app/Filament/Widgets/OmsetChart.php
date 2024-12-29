<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class OmsetChart extends ChartWidget
{
    protected static ?string $heading = 'Chart';
    protected static ?int $sort = 1;
    public ?string $filter = 'today';

    protected function getData(): array
    {
        $activeFilter = $this->filter;

        $dateRange = match ($activeFilter) {
            'today' => [
                'start' => now()->startOfDay(),
                'end' => now()->endOfDay(),
                'period' => 'perHour',
            ],
            'week' => [
                'start' => now()->startOfWeek(),
                'end' => now()->endOfWeek(),
                'period' => 'perDay',
            ],
            'month' => [
                'start' => now()->startOfMonth(),
                'end' => now()->endOfMonth(),
                'period' => 'perDay'
            ],
            'year' => [
                'start' => now()->startOfYear(),
                'end' => now()->endOfYear(),
                'period' => 'perMonth'
            ],
            default => [
                'start' => now()->startOfYear(),
                'end' => now()->endOfYear(),
                'period' => 'perMonth'
            ],
        };

        $query = Trend::model(Order::class)
        ->between(
            start: $dateRange['start'],
            end: $dateRange['end'],
        );

        if ($dateRange['period'] === 'perHour') {
            $data = $query->perHour();
        } elseif ($dateRange['period'] === 'perDay') {
            $data = $query->perDay();
        } elseif ($dateRange['period'] === 'perMonth') {
            $data = $query->perMonth();
        } else {
            $data = $query->perMonth();
        }

        $data = $data->sum('total_price');

        $labels = $data->map(function (TrendValue $value) use ($dateRange) {
            $date = Carbon::parse($value->date);

            if($dateRange['period'] === 'perHour') {
                return $date->format('H:i');
            } elseif ($dateRange['period'] === 'perDay') {
                return $date->format('d-m');
            }
            return $date->format('M Y');
        });

    return [
        'datasets' => [
            [
                'label' => 'Omset' . ($this->getFilters()[$activeFilter] ?? ''),
                'data' => $data->map(fn (TrendValue $value) => $value->aggregate),
            ],
        ],
        'labels' => $labels,
    ];
    }

    protected function getFilters(): ?array
    {
        return [
            'today' => 'Hari Ini',
            'week' => 'Minggu Ini',
            'month' => 'Bulan Ini',
            'year' => 'Tahun Ini',
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}