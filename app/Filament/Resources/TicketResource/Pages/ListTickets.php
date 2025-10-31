<?php

namespace App\Filament\Resources\TicketResource\Pages;

use App\Filament\Resources\TicketResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListTickets extends ListRecords
{
    protected static string $resource = TicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'open' => Tab::make('Open Tickets')
                ->icon('heroicon-o-inbox')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNotIn('status', ['resolved', 'completed', 'cancelled', 'refunded', 'failed']))
                ->badge(fn () => \App\Models\Ticket::whereNotIn('status', ['resolved', 'completed', 'cancelled', 'refunded', 'failed'])->count()),
            'resolved' => Tab::make('Resolved Tickets')
                ->icon('heroicon-o-check-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', ['resolved', 'completed', 'cancelled', 'refunded', 'failed']))
                ->badge(fn () => \App\Models\Ticket::whereIn('status', ['resolved', 'completed', 'cancelled', 'refunded', 'failed'])->count()),
        ];
    }
}
