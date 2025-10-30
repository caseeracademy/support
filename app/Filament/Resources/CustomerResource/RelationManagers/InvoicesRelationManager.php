<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class InvoicesRelationManager extends RelationManager
{
    protected static string $relationship = 'invoices';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('invoice_number')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('invoice_number')
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Invoice #')
                    ->searchable()
                    ->copyable()
                    ->fontFamily('mono'),

                Tables\Columns\TextColumn::make('title')
                    ->limit(30)
                    ->searchable(),

                Tables\Columns\TextColumn::make('formatted_total_amount')
                    ->label('Total')
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('formatted_remaining_amount')
                    ->label('Outstanding')
                    ->color(fn ($record) => $record->remaining_amount > 0 ? 'danger' : 'success'),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'gray' => 'draft',
                        'info' => 'sent',
                        'success' => 'paid',
                        'danger' => 'overdue',
                        'warning' => 'cancelled',
                    ]),

                Tables\Columns\TextColumn::make('due_date')
                    ->date('M j, Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'sent' => 'Sent',
                        'paid' => 'Paid',
                        'overdue' => 'Overdue',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('view_all')
                    ->label('View All Invoices')
                    ->url('/admin/invoices')
                    ->icon('heroicon-o-eye'),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->url(fn ($record): string => "/admin/invoices/{$record->id}")
                    ->icon('heroicon-o-eye'),
            ])
            ->emptyStateHeading('No invoices yet')
            ->emptyStateDescription('Create an invoice for this customer')
            ->defaultSort('created_at', 'desc');
    }
}
