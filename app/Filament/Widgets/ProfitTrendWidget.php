<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class ProfitTrendWidget extends ChartWidget
{
    protected static ?string $heading = 'Profit Trend';

    protected static ?int $sort = 6;

    public static function canView(): bool
    {
        return Auth::user()?->hasRole('admin') ?? false;
    }

    public ?string $filter = 'last_30_days';

    protected function getData(): array
    {
        $period = $this->getPeriod();
        $days = $period['days'];
        $startDate = $period['start'];

        $labels = [];
        $profitData = [];

        for ($i = 0; $i < $days; $i++) {
            $date = $startDate->copy()->addDays($i);
            $labels[] = $date->format($days > 31 ? 'M j' : 'j');

            $dailyIncome = Transaction::where('type', 'income')
                ->where('status', 'completed')
                ->whereDate('transaction_date', $date)
                ->sum('amount');

            $dailyExpenses = Transaction::where('type', 'expense')
                ->where('status', 'completed')
                ->whereDate('transaction_date', $date)
                ->sum('amount');

            $dailyProfit = $dailyIncome - $dailyExpenses;
            $profitData[] = round($dailyProfit, 2);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Daily Profit',
                    'data' => $profitData,
                    'backgroundColor' => function ($context) {
                        return $context->parsed.y >= 0
                            ? 'rgba(34, 197, 94, 0.2)'
                            : 'rgba(239, 68, 68, 0.2)';
                    },
                    'borderColor' => function ($context) {
                        return $context->parsed.y >= 0
                            ? 'rgb(34, 197, 94)'
                            : 'rgb(239, 68, 68)';
                    },
                    'borderWidth' => 2,
                    'fill' => 'origin',
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
            'last_7_days' => 'Last 7 days',
            'last_30_days' => 'Last 30 days',
            'last_90_days' => 'Last 90 days',
        ];
    }

    protected function getPeriod(): array
    {
        return match ($this->filter) {
            'last_7_days' => [
                'days' => 7,
                'start' => Carbon::now()->subDays(6),
            ],
            'last_30_days' => [
                'days' => 30,
                'start' => Carbon::now()->subDays(29),
            ],
            'last_90_days' => [
                'days' => 90,
                'start' => Carbon::now()->subDays(89),
            ],
            default => [
                'days' => 30,
                'start' => Carbon::now()->subDays(29),
            ],
        };
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
                'x' => [
                    'grid' => [
                        'display' => false,
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                ],
            ],
            'elements' => [
                'point' => [
                    'radius' => 3,
                    'hoverRadius' => 6,
                ],
            ],
        ];
    }
}
