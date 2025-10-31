<?php

namespace App\Filament\Widgets;

use App\Models\Ticket;
use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class PaymentApprovalWidget extends BaseWidget
{
    public static function canView(): bool
    {
        return Auth::user()?->hasRole('admin') ?? false;
    }

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Ticket::query()
                    ->with(['customer', 'paymentApprovedBy'])
                    ->where('payment_status', 'pending')
                    ->whereNotNull('total_amount')
                    ->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Ticket #')
                    ->prefix('#')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('subject')
                    ->searchable()
                    ->limit(40)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();

                        return strlen($state) > 40 ? $state : null;
                    }),

                Tables\Columns\TextColumn::make('formatted_total_amount')
                    ->label('Amount')
                    ->weight('medium')
                    ->color('primary'),

                Tables\Columns\TextColumn::make('payment_progress')
                    ->label('Progress')
                    ->getStateUsing(function (Ticket $record): string {
                        if (! $record->total_amount) {
                            return 'N/A';
                        }

                        $percentage = round($record->payment_progress, 1);

                        return "{$percentage}%";
                    })
                    ->color(fn (Ticket $record): string => match (true) {
                        $record->payment_progress >= 100 => 'success',
                        $record->payment_progress >= 50 => 'warning',
                        default => 'danger',
                    }),

                Tables\Columns\BadgeColumn::make('is_overdue')
                    ->label('Status')
                    ->getStateUsing(function (Ticket $record): string {
                        if ($record->is_overdue) {
                            return 'overdue';
                        }

                        if ($record->payment_due_date && $record->payment_due_date->diffInDays(now()) <= 3) {
                            return 'due_soon';
                        }

                        return 'pending';
                    })
                    ->colors([
                        'danger' => 'overdue',
                        'warning' => 'due_soon',
                        'info' => 'pending',
                    ])
                    ->icons([
                        'heroicon-o-exclamation-triangle' => 'overdue',
                        'heroicon-o-clock' => 'due_soon',
                        'heroicon-o-banknotes' => 'pending',
                    ]),

                Tables\Columns\TextColumn::make('payment_due_date')
                    ->label('Due Date')
                    ->date('M j, Y')
                    ->sortable()
                    ->placeholder('No due date'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('overdue')
                    ->query(fn ($query) => $query->where('payment_due_date', '<', now()))
                    ->label('Overdue payments'),

                Tables\Filters\Filter::make('due_soon')
                    ->query(fn ($query) => $query->where('payment_due_date', '>', now())
                        ->where('payment_due_date', '<=', now()->addDays(7)))
                    ->label('Due within 7 days'),
            ])
            ->actions([
                Tables\Actions\Action::make('approve_payment')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->label('Payment Amount')
                            ->numeric()
                            ->prefix('$')
                            ->step(0.01)
                            ->default(fn (Ticket $record) => $record->remaining_amount)
                            ->required()
                            ->helperText('Enter the amount received from the customer'),
                    ])
                    ->action(function (Ticket $record, array $data): void {
                        $record->addPayment($data['amount'], Auth::user());
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Approve Payment')
                    ->modalDescription('Confirm the payment amount received from the customer.')
                    ->successNotificationTitle('Payment approved successfully'),

                Tables\Actions\Action::make('mark_as_paid')
                    ->label('Mark Paid')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->action(function (Ticket $record): void {
                        $record->markAsPaid(Auth::user());
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Mark as Fully Paid')
                    ->modalDescription('This will mark the entire amount as paid.')
                    ->successNotificationTitle('Ticket marked as paid'),

                Tables\Actions\Action::make('view_ticket')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->url(fn (Ticket $record): string => "/admin/tickets/{$record->id}/edit")
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('bulk_approve')
                    ->label('Mark as Paid')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Mark Selected as Paid')
                    ->modalDescription('This will mark all selected tickets as fully paid.')
                    ->action(function ($records): void {
                        $user = Auth::user();
                        foreach ($records as $record) {
                            $record->markAsPaid($user);
                        }
                    })
                    ->successNotificationTitle('Selected tickets marked as paid'),
            ])
            ->heading('Payment Approval Queue')
            ->description('Tickets awaiting payment approval')
            ->emptyStateHeading('No payments pending approval')
            ->emptyStateDescription('All payments are up to date!')
            ->emptyStateIcon('heroicon-o-check-badge')
            ->defaultSort('payment_due_date', 'asc')
            ->poll('30s'); // Auto-refresh every 30 seconds
    }
}

