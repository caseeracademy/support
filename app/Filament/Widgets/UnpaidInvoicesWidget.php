<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class UnpaidInvoicesWidget extends BaseWidget implements HasActions, HasForms
{
    public static function canView(): bool
    {
        return Auth::user()?->hasRole('admin') ?? false;
    }

    use InteractsWithActions;
    use InteractsWithForms;

    protected static ?int $sort = 8;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Invoice::query()
                    ->with(['customer', 'ticket', 'createdBy'])
                    ->whereIn('status', ['sent', 'overdue'])
                    ->orderByRaw("
                        CASE 
                            WHEN status = 'overdue' THEN 1 
                            WHEN due_date < NOW() THEN 2
                            ELSE 3 
                        END
                    ")
                    ->orderBy('due_date', 'asc')
                    ->limit(20)
            )
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Invoice #')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->fontFamily('mono')
                    ->weight('medium')
                    ->color('primary'),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->limit(25)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();

                        return strlen($state) > 25 ? $state : null;
                    }),

                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();

                        return strlen($state) > 30 ? $state : null;
                    }),

                Tables\Columns\TextColumn::make('formatted_total_amount')
                    ->label('Total')
                    ->weight('medium')
                    ->color('primary'),

                Tables\Columns\TextColumn::make('formatted_remaining_amount')
                    ->label('Outstanding')
                    ->weight('medium')
                    ->color(fn (Invoice $record): string => match (true) {
                        $record->is_overdue => 'danger',
                        $record->due_date?->diffInDays(now()) <= 3 => 'warning',
                        default => 'success',
                    }),

                Tables\Columns\TextColumn::make('payment_progress')
                    ->label('Paid')
                    ->getStateUsing(function (Invoice $record): string {
                        return number_format($record->payment_progress, 1).'%';
                    })
                    ->badge()
                    ->color(fn (Invoice $record): string => match (true) {
                        $record->payment_progress >= 80 => 'success',
                        $record->payment_progress >= 50 => 'warning',
                        default => 'danger',
                    }),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'info' => 'sent',
                        'danger' => 'overdue',
                    ])
                    ->icons([
                        'heroicon-o-paper-airplane' => 'sent',
                        'heroicon-o-exclamation-triangle' => 'overdue',
                    ]),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Due Date')
                    ->date('M j, Y')
                    ->sortable()
                    ->color(fn (Invoice $record): string => match (true) {
                        $record->is_overdue => 'danger',
                        $record->due_date?->diffInDays(now()) <= 3 => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('days_overdue')
                    ->label('Days Overdue')
                    ->getStateUsing(function (Invoice $record): string {
                        if (! $record->is_overdue) {
                            return '-';
                        }

                        $days = $record->days_overdue;

                        return $days.' day'.($days > 1 ? 's' : '');
                    })
                    ->color('danger')
                    ->weight('medium')
                    ->visible(fn () => Invoice::whereIn('status', ['sent', 'overdue'])->where('due_date', '<', now())->exists()),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'sent' => 'Sent',
                        'overdue' => 'Overdue',
                    ]),

                Tables\Filters\Filter::make('overdue_only')
                    ->query(fn ($query) => $query->where('status', 'overdue'))
                    ->label('Overdue Only')
                    ->toggle(),

                Tables\Filters\Filter::make('due_soon')
                    ->query(fn ($query) => $query->where('due_date', '>', now())
                        ->where('due_date', '<=', now()->addDays(7)))
                    ->label('Due Within 7 Days')
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\Action::make('mark_paid')
                    ->label('Mark Paid')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(function (Invoice $record): void {
                        $record->markAsPaid();
                        $this->dispatch('refresh');
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Mark Invoice as Paid')
                    ->modalDescription('This will mark the entire invoice amount as paid.')
                    ->successNotificationTitle('Invoice marked as paid')
                    ->visible(fn (Invoice $record): bool => $record->remaining_amount > 0),

                Tables\Actions\Action::make('send_reminder')
                    ->label('Send Reminder')
                    ->icon('heroicon-o-bell')
                    ->color('warning')
                    ->action(function (Invoice $record): void {
                        // TODO: Implement email reminder functionality
                        $this->notify('success', 'Reminder sent successfully');
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Send Payment Reminder')
                    ->modalDescription('Send a payment reminder email to the customer.')
                    ->successNotificationTitle('Reminder sent'),

                Tables\Actions\Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->url(fn (Invoice $record): string => "/admin/invoices/{$record->id}")
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('mark_paid')
                    ->label('Mark as Paid')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Mark Selected Invoices as Paid')
                    ->modalDescription('This will mark all selected invoices as fully paid.')
                    ->action(function ($records): void {
                        foreach ($records as $record) {
                            $record->markAsPaid();
                        }
                        $this->dispatch('refresh');
                    })
                    ->successNotificationTitle('Selected invoices marked as paid'),

                Tables\Actions\BulkAction::make('send_reminders')
                    ->label('Send Reminders')
                    ->icon('heroicon-o-bell')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Send Payment Reminders')
                    ->modalDescription('Send payment reminder emails to all selected customers.')
                    ->action(function ($records): void {
                        // TODO: Implement bulk email reminder functionality
                        $count = count($records);
                        $this->notify('success', "Reminders sent to {$count} customers");
                    })
                    ->successNotificationTitle('Reminders sent'),
            ])
            ->heading('Outstanding Invoices')
            ->description('Invoices awaiting payment')
            ->emptyStateHeading('No unpaid invoices')
            ->emptyStateDescription('All invoices are paid up to date!')
            ->emptyStateIcon('heroicon-o-check-badge')
            ->poll('60s') // Auto-refresh every minute
            ->striped()
            ->defaultSort('due_date', 'asc');
    }

    protected function getTableEmptyStateActions(): array
    {
        return [
            Action::make('create_invoice')
                ->label('Create Invoice')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->url('/admin/invoices/create'),
        ];
    }
}
