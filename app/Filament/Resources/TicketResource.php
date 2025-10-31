<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TicketResource\Pages;
use App\Models\Ticket;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TicketResource extends Resource
{
    protected static ?string $model = Ticket::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationGroup = 'Tickets';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Ticket Details')
                    ->description('Basic ticket information and assignment')
                    ->schema([
                        Forms\Components\Select::make('customer_id')
                            ->relationship('customer', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\TextInput::make('course_name')
                            ->label('Course Name')
                            ->placeholder('Related course or product'),
                        Forms\Components\TextInput::make('subject')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Brief description of the issue'),
                        Forms\Components\Textarea::make('description')
                            ->placeholder('Detailed description of the ticket')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending Payment',
                                'processing' => 'Processing',
                                'on-hold' => 'On Hold',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled',
                                'refunded' => 'Refunded',
                                'failed' => 'Failed',
                                'resolved' => 'Resolved',
                            ])
                            ->default('processing')
                            ->required(),
                        Forms\Components\Select::make('priority')
                            ->options([
                                'low' => 'Low',
                                'medium' => 'Medium',
                                'high' => 'High',
                            ])
                            ->default('medium')
                            ->required(),
                        Forms\Components\Select::make('assigned_to')
                            ->relationship('assignedTo', 'name')
                            ->searchable()
                            ->preload(),
                    ])
                    ->columns(2),

                self::getPaymentSection(),
            ]);
    }

    private static function getPaymentSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Payment Information')
            ->description('Order payment details and status tracking')
            ->schema([
                Forms\Components\Select::make('payment_status')
                    ->options([
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                        'partial' => 'Partially Paid',
                        'refunded' => 'Refunded',
                        'cancelled' => 'Cancelled',
                    ])
                    ->default('pending')
                    ->required()
                    ->live(),

                Forms\Components\TextInput::make('total_amount')
                    ->numeric()
                    ->prefix('$')
                    ->step(0.01)
                    ->minValue(0)
                    ->placeholder('0.00'),

                Forms\Components\TextInput::make('paid_amount')
                    ->numeric()
                    ->prefix('$')
                    ->step(0.01)
                    ->default(0)
                    ->minValue(0)
                    ->placeholder('0.00'),

                Forms\Components\Select::make('currency')
                    ->options([
                        'USD' => 'USD ($)',
                        'EUR' => 'EUR (€)',
                        'GBP' => 'GBP (£)',
                    ])
                    ->default('USD'),

                Forms\Components\TextInput::make('order_reference')
                    ->placeholder('Order ID or reference number')
                    ->maxLength(255),

                Forms\Components\TextInput::make('payment_reference')
                    ->placeholder('Payment transaction ID')
                    ->maxLength(255),

                Forms\Components\DateTimePicker::make('payment_due_date')
                    ->placeholder('When payment is due'),

                Forms\Components\DateTimePicker::make('paid_at')
                    ->visible(fn (Forms\Get $get) => in_array($get('payment_status'), ['paid', 'partial'])),
            ])
            ->columns(2)
            ->collapsible();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->limit(20)
                    ->tooltip(fn (Ticket $record) => $record->customer->name)
                    ->url(fn (Ticket $record): string => static::getUrl('details', ['record' => $record->id]))
                    ->color('primary')
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('subject')
                    ->searchable()
                    ->sortable()
                    ->limit(30)
                    ->tooltip(fn (Ticket $record) => $record->subject),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'primary' => 'processing',
                        'warning' => 'on-hold',
                        'success' => 'completed',
                        'danger' => 'cancelled',
                        'gray' => 'refunded',
                        'danger' => 'failed',
                        'info' => 'resolved',
                    ])
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('payment_status')
                    ->label('Payment')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'paid',
                        'info' => 'partial',
                        'danger' => 'cancelled',
                        'gray' => 'refunded',
                    ])
                    ->sortable(),

                Tables\Columns\TextColumn::make('formatted_total_amount')
                    ->label('Amount')
                    ->getStateUsing(fn (Ticket $record): string => $record->total_amount ? '$'.number_format($record->total_amount, 2) : 'N/A'
                    )
                    ->weight('medium')
                    ->sortable(query: fn ($query, $direction) => $query->orderBy('total_amount', $direction)),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending Payment',
                        'processing' => 'Processing',
                        'on-hold' => 'On Hold',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                        'refunded' => 'Refunded',
                        'failed' => 'Failed',
                        'resolved' => 'Resolved',
                    ]),
                Tables\Filters\SelectFilter::make('priority')
                    ->options([
                        'low' => 'Low',
                        'medium' => 'Medium',
                        'high' => 'High',
                    ]),
                Tables\Filters\SelectFilter::make('payment_status')
                    ->options([
                        'pending' => 'Payment Pending',
                        'paid' => 'Paid',
                        'partial' => 'Partially Paid',
                        'refunded' => 'Refunded',
                        'cancelled' => 'Payment Cancelled',
                    ]),
                Tables\Filters\Filter::make('overdue_payments')
                    ->query(fn ($query) => $query->where('payment_status', 'pending')
                        ->whereNotNull('payment_due_date')
                        ->where('payment_due_date', '<', now()))
                    ->label('Overdue Payments'),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\Action::make('approve_payment')
                    ->label('Approve Payment')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Ticket $record): bool => $record->payment_status === 'pending')
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->label('Payment Amount')
                            ->numeric()
                            ->prefix('$')
                            ->step(0.01)
                            ->default(fn (Ticket $record) => $record->remaining_amount)
                            ->required(),

                        Forms\Components\Select::make('payment_method_id')
                            ->label('Deposit To Account')
                            ->options(\App\Models\PaymentMethod::where('is_active', true)->pluck('name', 'id'))
                            ->required()
                            ->searchable()
                            ->preload()
                            ->default(fn () => \App\Models\PaymentMethod::where('is_active', true)->where('slug', 'business-bank-account')->value('id')
                                ?? \App\Models\PaymentMethod::where('is_active', true)->value('id'))
                            ->helperText('Select which account this payment will be deposited to'),
                    ])
                    ->action(function (Ticket $record, array $data): void {
                        $record->addPayment($data['amount'], auth()->user(), $data['payment_method_id']);
                    })
                    ->requiresConfirmation()
                    ->successNotificationTitle('Payment approved successfully'),

                Tables\Actions\Action::make('mark_as_paid')
                    ->label('Mark as Paid')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn (Ticket $record): bool => $record->payment_status === 'pending')
                    ->action(function (Ticket $record): void {
                        $record->markAsPaid(auth()->user());
                    })
                    ->requiresConfirmation()
                    ->successNotificationTitle('Ticket marked as paid'),

                Tables\Actions\Action::make('send_whatsapp')
                    ->label('Message')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->color('info')
                    ->url(fn (Ticket $record): string => "https://wa.me/{$record->customer->phone_number}")
                    ->openUrlInNewTab(),
            ])
            ->recordUrl(fn (Ticket $record): string => static::getUrl('details', ['record' => $record]))
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTickets::route('/'),
            'create' => Pages\CreateTicket::route('/create'),
            'edit' => Pages\EditTicket::route('/{record}/edit'),
            'details' => Pages\TicketDetails::route('/{record}'),
        ];
    }
}
