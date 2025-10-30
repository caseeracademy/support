<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\TicketResource;
use App\Models\Ticket;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class RecentTicketsWidget extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public function table(Table $table): Table
    {
        $user = Auth::user();
        $query = Ticket::query()->with(['customer', 'assignedTo']);

        // Filter by assigned user for customer care
        if ($user && $user->hasRole('customer_care')) {
            $query->where('assigned_to', $user->id);
        }

        return $table
            ->query(
                $query
                    ->latest()
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('subject')
                    ->searchable()
                    ->limit(40)
                    ->tooltip(fn (Ticket $record): string => $record->subject),

                Tables\Columns\TextColumn::make('customer.name')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'danger' => 'open',
                        'warning' => 'pending',
                        'success' => 'resolved',
                        'secondary' => 'closed',
                    ])
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('priority')
                    ->colors([
                        'danger' => 'high',
                        'warning' => 'medium',
                        'success' => 'low',
                    ])
                    ->sortable(),

                Tables\Columns\TextColumn::make('assignedTo.name')
                    ->label('Assigned To')
                    ->default('Unassigned')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->since(),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->url(fn (Ticket $record): string => TicketResource::getUrl('details', ['record' => $record]))
                    ->icon('heroicon-m-eye'),
            ])
            ->heading($user && $user->hasRole('customer_care') ? 'My Assigned Tickets' : 'Recent Tickets')
            ->description($user && $user->hasRole('customer_care') ? 'Tickets assigned to you' : 'Latest 5 support tickets');
    }
}
