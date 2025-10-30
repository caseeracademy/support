<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class CashFlowWidget extends ChartWidget
{
    protected static ?string $heading = 'Cash Flow Analysis';

    protected static ?int $sort = 7;

    public static function canView(): bool
    {
        return Auth::user()?->hasRole('admin') ?? false;
    }

    protected int|string|array $columnSpan = 'full';

    public ?string $filter = 'last_6_months';

    protected function getData(): array
    {
        $period = match ($this->filter) {
            'last_3_months' => 3,
            'last_6_months' => 6,
            'last_12_months' => 12,
            default => 6,
        };

        $months = [];
        $cumulativeData = [];
        $inFlowData = [];
        $outFlowData = [];

        $cumulativeBalance = 0;

        for ($i = $period - 1; $i >= 0; $i--) {
            $startOfMonth = Carbon::now()->subMonths($i)->startOfMonth();
            $endOfMonth = $startOfMonth->copy()->endOfMonth();

            $months[] = $startOfMonth->format('M Y');

            $monthlyIncome = Transaction::where('type', 'income')
                ->where('status', 'completed')
                ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
                ->sum('amount');

            $monthlyExpenses = Transaction::where('type', 'expense')
                ->where('status', 'completed')
                ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
                ->sum('amount');

            $netFlow = $monthlyIncome - $monthlyExpenses;
            $cumulativeBalance += $netFlow;

            $inFlowData[] = round($monthlyIncome, 2);
            $outFlowData[] = round(-$monthlyExpenses, 2); // Negative for visual effect
            $cumulativeData[] = round($cumulativeBalance, 2);
        }

        return [
            'datasets' => [
                [
                    'type' => 'bar',
                    'label' => 'Cash Inflow',
                    'data' => $inFlowData,
                    'backgroundColor' => 'rgba(34, 197, 94, 0.7)',
                    'borderColor' => 'rgb(34, 197, 94)',
                    'borderWidth' => 1,
                    'yAxisID' => 'y',
                ],
                [
                    'type' => 'bar',
                    'label' => 'Cash Outflow',
                    'data' => $outFlowData,
                    'backgroundColor' => 'rgba(239, 68, 68, 0.7)',
                    'borderColor' => 'rgb(239, 68, 68)',
                    'borderWidth' => 1,
                    'yAxisID' => 'y',
                ],
                [
                    'type' => 'line',
                    'label' => 'Cumulative Balance',
                    'data' => $cumulativeData,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'borderWidth' => 3,
                    'fill' => false,
                    'yAxisID' => 'y1',
                    'tension' => 0.3,
                ],
            ],
            'labels' => $months,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getFilters(): ?array
    {
        return [
            'last_3_months' => 'Last 3 months',
            'last_6_months' => 'Last 6 months',
            'last_12_months' => 'Last 12 months',
        ];
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'interaction' => [
                'mode' => 'index',
                'intersect' => false,
            ],
            'scales' => [
                'y' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'left',
                    'title' => [
                        'display' => true,
                        'text' => 'Monthly Cash Flow ($)',
                    ],
                    'ticks' => [
                        'callback' => '($) + value.toLocaleString()',
                    ],
                ],
                'y1' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'right',
                    'title' => [
                        'display' => true,
                        'text' => 'Cumulative Balance ($)',
                    ],
                    'grid' => [
                        'drawOnChartArea' => false,
                    ],
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
                    'callbacks' => [
                        'label' => 'function(context) {
                            let label = context.dataset.label || "";
                            if (label) {
                                label += ": ";
                            }
                            if (context.parsed.y !== null) {
                                label += "$" + Math.abs(context.parsed.y).toLocaleString();
                            }
                            return label;
                        }',
                    ],
                ],
            ],
        ];
    }
}
