<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class CategoryBreakdownWidget extends ChartWidget
{
    protected static ?string $heading = 'Expenses by Category';

    protected static ?int $sort = 5;

    public static function canView(): bool
    {
        return Auth::user()?->hasRole('admin') ?? false;
    }

    public ?string $filter = 'this_month';

    protected function getData(): array
    {
        $period = $this->getPeriod();

        $categoryData = Transaction::query()
            ->where('type', 'expense')
            ->where('status', 'completed')
            ->whereBetween('transaction_date', $period)
            ->with('category')
            ->selectRaw('category_id, SUM(amount) as total_amount')
            ->groupBy('category_id')
            ->orderBy('total_amount', 'desc')
            ->limit(10)
            ->get();

        $labels = [];
        $data = [];
        $colors = [];

        foreach ($categoryData as $item) {
            if ($item->category) {
                $labels[] = $item->category->name;
                $data[] = round($item->total_amount, 2);
                $colors[] = $item->category->color ?? '#3B82F6';
            }
        }

        return [
            'datasets' => [
                [
                    'data' => $data,
                    'backgroundColor' => $colors,
                    'borderWidth' => 0,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getFilters(): ?array
    {
        return [
            'this_month' => 'This month',
            'last_month' => 'Last month',
            'this_quarter' => 'This quarter',
            'this_year' => 'This year',
        ];
    }

    protected function getPeriod(): array
    {
        return match ($this->filter) {
            'this_month' => [
                Carbon::now()->startOfMonth(),
                Carbon::now()->endOfMonth(),
            ],
            'last_month' => [
                Carbon::now()->subMonth()->startOfMonth(),
                Carbon::now()->subMonth()->endOfMonth(),
            ],
            'this_quarter' => [
                Carbon::now()->startOfQuarter(),
                Carbon::now()->endOfQuarter(),
            ],
            'this_year' => [
                Carbon::now()->startOfYear(),
                Carbon::now()->endOfYear(),
            ],
            default => [
                Carbon::now()->startOfMonth(),
                Carbon::now()->endOfMonth(),
            ],
        };
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => 'function(context) {
                            return context.label + ": $" + context.parsed.toLocaleString();
                        }',
                    ],
                ],
            ],
            'maintainAspectRatio' => false,
        ];
    }
}
