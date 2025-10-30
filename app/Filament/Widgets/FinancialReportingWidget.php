<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class FinancialReportingWidget extends ChartWidget
{
    protected static ?string $heading = 'Monthly Income vs Expenses';

    protected static ?int $sort = 4;

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
        $incomeData = [];
        $expenseData = [];

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

            $incomeData[] = round($monthlyIncome, 2);
            $expenseData[] = round($monthlyExpenses, 2);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Income',
                    'data' => $incomeData,
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'borderColor' => 'rgb(34, 197, 94)',
                    'borderWidth' => 2,
                    'fill' => true,
                ],
                [
                    'label' => 'Expenses',
                    'data' => $expenseData,
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'borderColor' => 'rgb(239, 68, 68)',
                    'borderWidth' => 2,
                    'fill' => true,
                ],
            ],
            'labels' => $months,
        ];
    }

    protected function getType(): string
    {
        return 'line';
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
            'interaction' => [
                'mode' => 'nearest',
                'axis' => 'x',
                'intersect' => false,
            ],
        ];
    }
}
