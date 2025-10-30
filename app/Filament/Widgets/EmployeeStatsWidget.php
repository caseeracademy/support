<?php

namespace App\Filament\Widgets;

use App\Models\Employee;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class EmployeeStatsWidget extends BaseWidget
{
    public static function canView(): bool
    {
        return Auth::user()?->hasRole('admin') ?? false;
    }

    protected function getStats(): array
    {
        $activeEmployees = Employee::active()->count();
        $employeesWithLogin = Employee::whereNotNull('user_id')->count();
        $totalPayroll = Employee::active()->sum('base_salary');
        $averageSalary = Employee::active()->avg('base_salary') ?? 0;

        return [
            Stat::make('Active Employees', $activeEmployees)
                ->description($employeesWithLogin.' with system access')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('success'),

            Stat::make('Monthly Payroll', '$'.number_format($totalPayroll, 0))
                ->description('Average: $'.number_format($averageSalary, 0))
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('info'),

            Stat::make('Departments', Employee::getDepartments() ? count(Employee::getDepartments()) : 0)
                ->description(Employee::getDepartments() ? collect(Employee::getDepartments())->slice(0, 3)->implode(', ') : 'None')
                ->descriptionIcon('heroicon-m-building-office')
                ->color('warning'),
        ];
    }
}
