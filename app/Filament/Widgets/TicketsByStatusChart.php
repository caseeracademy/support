<?php

namespace App\Filament\Widgets;

use App\Models\Ticket;
use Filament\Widgets\ChartWidget;

class TicketsByStatusChart extends ChartWidget
{
    protected static ?string $heading = 'Tickets by Status';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $statuses = [
            'open' => Ticket::where('status', 'open')->count(),
            'pending' => Ticket::where('status', 'pending')->count(),
            'resolved' => Ticket::where('status', 'resolved')->count(),
            'closed' => Ticket::where('status', 'closed')->count(),
        ];

        return [
            'datasets' => [
                [
                    'label' => 'Tickets',
                    'data' => array_values($statuses),
                    'backgroundColor' => [
                        'rgb(239, 68, 68)',  // red for open
                        'rgb(251, 191, 36)', // yellow for pending
                        'rgb(34, 197, 94)',  // green for resolved
                        'rgb(156, 163, 175)', // gray for closed
                    ],
                ],
            ],
            'labels' => ['Open', 'Pending', 'Resolved', 'Closed'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
        ];
    }
}

