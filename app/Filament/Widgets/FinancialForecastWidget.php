<?php

namespace App\Filament\Widgets;

use App\Services\FinancialAnalyticsService;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class FinancialForecastWidget extends ChartWidget
{
    protected static ?string $heading = 'Revenue Forecast';

    protected static ?int $sort = 9;

    public static function canView(): bool
    {
        return Auth::user()?->hasRole('admin') ?? false;
    }

    protected int|string|array $columnSpan = 'full';

    public ?string $filter = 'linear';

    protected function getData(): array
    {
        $analyticsService = app(FinancialAnalyticsService::class);
        $forecastData = $analyticsService->forecastRevenue(3, $this->filter);

        $labels = [];
        $historicalAmounts = [];
        $forecastedAmounts = [];

        // Add historical data
        foreach ($forecastData['historical_data'] as $data) {
            $labels[] = $data['month'];
            $historicalAmounts[] = round($data['amount'], 2);
        }

        // Add forecasted data
        foreach ($forecastData['forecasted_data'] as $data) {
            $labels[] = $data['month'];
            $forecastedAmounts[] = round($data['amount'], 2);
        }

        // Pad historical data with nulls for forecast period
        $historicalPadded = array_pad($historicalAmounts, count($labels), null);

        // Pad forecast data with nulls for historical period
        $forecastPadded = array_pad(
            array_fill(0, count($historicalAmounts), null),
            count($labels),
            0
        );

        // Fill in forecasted values
        foreach ($forecastData['forecasted_data'] as $i => $data) {
            $forecastPadded[count($historicalAmounts) + $i] = round($data['amount'], 2);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Historical Revenue',
                    'data' => $historicalPadded,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'borderWidth' => 3,
                    'fill' => true,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Forecasted Revenue',
                    'data' => $forecastPadded,
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'borderColor' => 'rgb(34, 197, 94)',
                    'borderWidth' => 3,
                    'borderDash' => [5, 5],
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getFilters(): ?array
    {
        return [
            'linear' => 'Linear Trend',
            'moving_average' => 'Moving Average',
            'seasonal' => 'Seasonal Pattern',
        ];
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => '($) + value.toLocaleString()',
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                ],
            ],
        ];
    }
}
