<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class FinancialOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return Auth::user()?->hasRole('admin') ?? false;
    }

    protected function getStats(): array
    {
        $currentMonth = Carbon::now()->startOfMonth();
        $currentYear = Carbon::now()->startOfYear();

        // Current month calculations
        $monthlyIncome = Transaction::where('type', 'income')
            ->where('status', 'completed')
            ->where('transaction_date', '>=', $currentMonth)
            ->sum('amount');

        $monthlyExpenses = Transaction::where('type', 'expense')
            ->where('status', 'completed')
            ->where('transaction_date', '>=', $currentMonth)
            ->sum('amount');

        $monthlyProfit = $monthlyIncome - $monthlyExpenses;

        // Previous month for comparison
        $previousMonth = $currentMonth->copy()->subMonth();
        $prevMonthIncome = Transaction::where('type', 'income')
            ->where('status', 'completed')
            ->whereBetween('transaction_date', [$previousMonth, $currentMonth])
            ->sum('amount');

        $prevMonthExpenses = Transaction::where('type', 'expense')
            ->where('status', 'completed')
            ->whereBetween('transaction_date', [$previousMonth, $currentMonth])
            ->sum('amount');

        // Yearly totals
        $yearlyIncome = Transaction::where('type', 'income')
            ->where('status', 'completed')
            ->where('transaction_date', '>=', $currentYear)
            ->sum('amount');

        $yearlyExpenses = Transaction::where('type', 'expense')
            ->where('status', 'completed')
            ->where('transaction_date', '>=', $currentYear)
            ->sum('amount');

        // Pending transactions
        $pendingIncome = Transaction::where('type', 'income')
            ->where('status', 'pending')
            ->sum('amount');

        return [
            Stat::make('Monthly Income', '$'.number_format($monthlyIncome, 2))
                ->description($this->getChangeDescription($monthlyIncome, $prevMonthIncome))
                ->descriptionIcon($monthlyIncome > $prevMonthIncome ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($monthlyIncome > $prevMonthIncome ? 'success' : 'danger')
                ->chart($this->getIncomeChart()),

            Stat::make('Monthly Expenses', '$'.number_format($monthlyExpenses, 2))
                ->description($this->getChangeDescription($monthlyExpenses, $prevMonthExpenses, true))
                ->descriptionIcon($monthlyExpenses < $prevMonthExpenses ? 'heroicon-m-arrow-trending-down' : 'heroicon-m-arrow-trending-up')
                ->color($monthlyExpenses < $prevMonthExpenses ? 'success' : 'danger')
                ->chart($this->getExpenseChart()),

            Stat::make('Monthly Profit', '$'.number_format($monthlyProfit, 2))
                ->description($monthlyProfit > 0 ? 'Profitable this month' : 'Loss this month')
                ->descriptionIcon($monthlyProfit > 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($monthlyProfit > 0 ? 'success' : 'danger'),

            Stat::make('Yearly Income', '$'.number_format($yearlyIncome, 2))
                ->description('Total income this year')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('info'),

            Stat::make('Yearly Expenses', '$'.number_format($yearlyExpenses, 2))
                ->description('Total expenses this year')
                ->descriptionIcon('heroicon-m-credit-card')
                ->color('warning'),

            Stat::make('Pending Revenue', '$'.number_format($pendingIncome, 2))
                ->description('Income pending approval')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
        ];
    }

    private function getChangeDescription(float $current, float $previous, bool $lowerIsBetter = false): string
    {
        if ($previous == 0) {
            return $current > 0 ? 'New income this month' : 'No income yet';
        }

        $change = (($current - $previous) / $previous) * 100;
        $direction = $change > 0 ? 'increase' : 'decrease';

        return abs($change).'% '.$direction.' from last month';
    }

    private function getIncomeChart(): array
    {
        // Get last 7 days of income data
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $amount = Transaction::where('type', 'income')
                ->where('status', 'completed')
                ->whereDate('transaction_date', $date)
                ->sum('amount');
            $data[] = $amount;
        }

        return $data;
    }

    private function getExpenseChart(): array
    {
        // Get last 7 days of expense data
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $amount = Transaction::where('type', 'expense')
                ->where('status', 'completed')
                ->whereDate('transaction_date', $date)
                ->sum('amount');
            $data[] = $amount;
        }

        return $data;
    }
}
