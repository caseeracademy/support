<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class TransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'transactions';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('reference_number')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('reference_number')
            ->columns([
                Tables\Columns\TextColumn::make('transaction_date')
                    ->label('Date')
                    ->date('M j, Y')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('type')
                    ->colors([
                        'success' => 'income',
                        'danger' => 'expense',
                    ]),

                Tables\Columns\TextColumn::make('title')
                    ->limit(30)
                    ->searchable(),

                Tables\Columns\TextColumn::make('formatted_amount')
                    ->label('Amount')
                    ->weight('medium')
                    ->color(fn ($record) => $record->type === 'income' ? 'success' : 'danger'),

                Tables\Columns\TextColumn::make('category.name')
                    ->badge(),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'completed',
                        'danger' => 'cancelled',
                    ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'income' => 'Income',
                        'expense' => 'Expense',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('view_all')
                    ->label('View All Transactions')
                    ->url('/admin/transactions')
                    ->icon('heroicon-o-eye'),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->url(fn ($record): string => "/admin/transactions/{$record->id}")
                    ->icon('heroicon-o-eye'),
            ])
            ->emptyStateHeading('No transactions yet')
            ->emptyStateDescription('No financial transactions for this customer')
            ->defaultSort('transaction_date', 'desc');
    }
}
