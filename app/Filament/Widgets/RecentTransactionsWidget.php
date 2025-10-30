<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class RecentTransactionsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    public static function canView(): bool
    {
        return Auth::user()?->hasRole('admin') ?? false;
    }

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Transaction::query()
                    ->with(['category', 'paymentMethod', 'createdBy'])
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('reference_number')
                    ->label('Reference')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->fontFamily('mono')
                    ->size('sm'),

                Tables\Columns\BadgeColumn::make('type')
                    ->colors([
                        'success' => 'income',
                        'danger' => 'expense',
                    ])
                    ->icons([
                        'heroicon-o-arrow-trending-up' => 'income',
                        'heroicon-o-arrow-trending-down' => 'expense',
                    ]),

                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();

                        return strlen($state) > 30 ? $state : null;
                    }),

                Tables\Columns\TextColumn::make('formatted_amount')
                    ->label('Amount')
                    ->weight('medium')
                    ->color(fn (Transaction $record): string => match ($record->type) {
                        'income' => 'success',
                        'expense' => 'danger',
                        default => 'primary',
                    }),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category')
                    ->badge()
                    ->color(fn (Transaction $record) => $record->category?->color ?? 'primary'),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'completed',
                        'danger' => 'cancelled',
                        'gray' => 'refunded',
                    ]),

                Tables\Columns\TextColumn::make('transaction_date')
                    ->label('Date')
                    ->date('M j, Y')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->url(fn (Transaction $record): string => "/admin/transactions/{$record->id}")
                    ->icon('heroicon-o-eye')
                    ->size('sm'),
            ])
            ->heading('Recent Transactions')
            ->description('Latest financial transactions in the system')
            ->emptyStateHeading('No transactions yet')
            ->emptyStateDescription('Create your first transaction to see it here.')
            ->emptyStateIcon('heroicon-o-currency-dollar')
            ->paginated(false);
    }
}
