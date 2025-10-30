<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use App\Models\Ticket;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalTickets = Ticket::count();
        $openTickets = Ticket::where('status', 'open')->count();
        $pendingTickets = Ticket::where('status', 'pending')->count();
        $resolvedTickets = Ticket::where('status', 'resolved')->count();
        $todayTickets = Ticket::whereDate('created_at', today())->count();

        return [
            Stat::make('Total Tickets', $totalTickets)
                ->description("{$openTickets} open, {$pendingTickets} pending, {$resolvedTickets} resolved")
                ->descriptionIcon('heroicon-m-ticket')
                ->color('primary')
                ->chart([$openTickets, $pendingTickets, $resolvedTickets]),

            Stat::make('Tickets Today', $todayTickets)
                ->description('Created in the last 24 hours')
                ->descriptionIcon('heroicon-m-calendar')
                ->color($todayTickets > 5 ? 'warning' : 'success'),

            Stat::make('Total Customers', Customer::count())
                ->description('Registered customers')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('success'),

            Stat::make('Support Team', User::count())
                ->description('Active support users')
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),
        ];
    }
}
