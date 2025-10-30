<?php

namespace App\Filament\Widgets;

use App\Models\Employee;
use App\Models\Payroll;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class HRStatsWidget extends BaseWidget
{
    protected static ?int $sort = 10;

    public static function canView(): bool
    {
        return Auth::user()?->hasRole('admin') ?? false;
    }

    protected function getStats(): array
    {
        $currentMonth = Carbon::now()->startOfMonth();

        $totalEmployees = Employee::active()->count();
        $fullTime = Employee::active()->where('employment_type', 'full_time')->count();
        $partTime = Employee::active()->where('employment_type', 'part_time')->count();

        $thisMonthPayroll = Payroll::where('payroll_period', now()->format('Y-m'))
            ->sum('net_pay');

        $pendingApprovals = Payroll::where('status', 'pending_approval')->count();

        return [
            Stat::make('Total Employees', $totalEmployees)
                ->description("{$fullTime} full-time, {$partTime} part-time")
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary'),

            Stat::make('This Month Payroll', '$'.number_format($thisMonthPayroll, 2))
                ->description('Total payroll expenses')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Pending Approvals', $pendingApprovals)
                ->description('Payroll awaiting approval')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pendingApprovals > 0 ? 'warning' : 'success'),

            Stat::make('Departments', count(Employee::getDepartments()))
                ->description('Active departments')
                ->descriptionIcon('heroicon-m-building-office')
                ->color('info'),
        ];
    }
}
