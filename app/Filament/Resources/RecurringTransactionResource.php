<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RecurringTransactionResource\Pages;
use App\Models\Customer;
use App\Models\RecurringTransaction;
use App\Models\Ticket;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class RecurringTransactionResource extends Resource
{
    protected static ?string $model = RecurringTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';

    protected static ?string $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'title';

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()?->hasRole('admin') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Recurring Transaction Details')
                    ->description('Basic information for the recurring transaction')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->options([
                                'income' => 'Income',
                                'expense' => 'Expense',
                            ])
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(fn (callable $set) => $set('category_id', null)),

                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Monthly Hosting, Quarterly Subscription'),

                        Forms\Components\Textarea::make('description')
                            ->placeholder('Detailed description of the recurring transaction')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Amount & Classification')
                    ->description('Financial details and categorization')
                    ->schema([
                        Forms\Components\TextInput::make('amount')
                            ->required()
                            ->numeric()
                            ->prefix('$')
                            ->step(0.01)
                            ->minValue(0.01)
                            ->maxValue(999999.99),

                        Forms\Components\Select::make('currency')
                            ->options([
                                'USD' => 'USD ($)',
                                'EUR' => 'EUR (€)',
                                'GBP' => 'GBP (£)',
                            ])
                            ->default('USD')
                            ->required(),

                        Forms\Components\Select::make('category_id')
                            ->relationship('category', 'name', fn (Builder $query, Forms\Get $get) => $query->when($get('type'), fn ($query, $type) => $query->where('type', $type))
                            )
                            ->required()
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('payment_method_id')
                            ->relationship('paymentMethod', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Recurrence Schedule')
                    ->description('Configure when and how often this transaction occurs')
                    ->schema([
                        Forms\Components\Select::make('frequency')
                            ->options([
                                'daily' => 'Daily',
                                'weekly' => 'Weekly',
                                'monthly' => 'Monthly',
                                'quarterly' => 'Quarterly',
                                'yearly' => 'Yearly',
                            ])
                            ->required()
                            ->reactive(),

                        Forms\Components\TextInput::make('interval')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->maxValue(12)
                            ->required()
                            ->helperText(fn (Forms\Get $get) => match ($get('frequency')) {
                                'daily' => 'Every X days (e.g., 1 = daily, 7 = weekly)',
                                'weekly' => 'Every X weeks (e.g., 1 = weekly, 2 = bi-weekly)',
                                'monthly' => 'Every X months (e.g., 1 = monthly, 3 = quarterly)',
                                'quarterly' => 'Every X quarters (usually 1)',
                                'yearly' => 'Every X years (usually 1)',
                                default => 'Frequency interval',
                            }),

                        Forms\Components\DatePicker::make('start_date')
                            ->required()
                            ->default(now())
                            ->afterOrEqual(now()->subDay()),

                        Forms\Components\DatePicker::make('end_date')
                            ->after('start_date')
                            ->helperText('Leave blank for indefinite recurrence'),

                        Forms\Components\TextInput::make('max_occurrences')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(1000)
                            ->helperText('Maximum number of times this should occur (leave blank for unlimited)'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Link to Records')
                    ->description('Optional: Link to customers or tickets')
                    ->schema([
                        Forms\Components\Select::make('recurrable_type')
                            ->options([
                                Customer::class => 'Customer',
                                Ticket::class => 'Ticket',
                            ])
                            ->placeholder('Select related record type')
                            ->reactive()
                            ->afterStateUpdated(fn (callable $set) => $set('recurrable_id', null)),

                        Forms\Components\Select::make('recurrable_id')
                            ->placeholder('Select related record')
                            ->options(function (Forms\Get $get) {
                                $type = $get('recurrable_type');
                                if ($type === Customer::class) {
                                    return Customer::pluck('name', 'id')->toArray();
                                } elseif ($type === Ticket::class) {
                                    return Ticket::with('customer')
                                        ->get()
                                        ->mapWithKeys(fn ($ticket) => [
                                            $ticket->id => "#{$ticket->id} - {$ticket->subject} ({$ticket->customer->name})",
                                        ])
                                        ->toArray();
                                }

                                return [];
                            })
                            ->searchable()
                            ->visible(fn (Forms\Get $get) => filled($get('recurrable_type'))),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Status & Control')
                    ->description('Activation and status settings')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->default(true)
                            ->helperText('Inactive recurring transactions will not create new transactions'),
                    ])
                    ->columns(1),

                Forms\Components\Hidden::make('created_by')
                    ->default(fn () => Auth::id()),

                Forms\Components\Hidden::make('next_due_date')
                    ->default(fn (Forms\Get $get) => $get('start_date')),

                Forms\Components\Hidden::make('occurrences_created')
                    ->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
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
                    ->sortable()
                    ->weight(FontWeight::Medium)
                    ->limit(40)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();

                        return strlen($state) > 40 ? $state : null;
                    }),

                Tables\Columns\TextColumn::make('formatted_amount')
                    ->label('Amount')
                    ->weight(FontWeight::SemiBold)
                    ->color(fn (RecurringTransaction $record): string => match ($record->type) {
                        'income' => 'success',
                        'expense' => 'danger',
                        default => 'primary',
                    })
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('amount', $direction)),

                Tables\Columns\TextColumn::make('frequency_display')
                    ->label('Frequency')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('next_due_date')
                    ->label('Next Due')
                    ->date('M j, Y')
                    ->sortable()
                    ->color(function (RecurringTransaction $record): string {
                        if (! $record->is_active) {
                            return 'gray';
                        }

                        $daysUntilDue = $record->next_due_date->diffInDays(now(), false);

                        if ($daysUntilDue <= 0) {
                            return 'danger';
                        }  // Overdue
                        if ($daysUntilDue <= 3) {
                            return 'warning';
                        } // Due soon

                        return 'success';
                    })
                    ->icon(function (RecurringTransaction $record): ?string {
                        if (! $record->is_active) {
                            return null;
                        }

                        $daysUntilDue = $record->next_due_date->diffInDays(now(), false);

                        if ($daysUntilDue <= 0) {
                            return 'heroicon-o-exclamation-triangle';
                        }
                        if ($daysUntilDue <= 3) {
                            return 'heroicon-o-clock';
                        }

                        return null;
                    }),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->color(fn (RecurringTransaction $record) => $record->category?->color ?? 'primary'),

                Tables\Columns\TextColumn::make('occurrences_created')
                    ->label('Completed')
                    ->sortable()
                    ->getStateUsing(function (RecurringTransaction $record): string {
                        $created = $record->occurrences_created;
                        $max = $record->max_occurrences;

                        return $max ? "{$created}/{$max}" : (string) $created;
                    })
                    ->color('info'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-play')
                    ->falseIcon('heroicon-o-pause')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('recurrable')
                    ->label('Linked To')
                    ->getStateUsing(function (RecurringTransaction $record): ?string {
                        if (! $record->recurrable) {
                            return null;
                        }

                        if ($record->recurrable instanceof Customer) {
                            return "Customer: {$record->recurrable->name}";
                        }

                        if ($record->recurrable instanceof Ticket) {
                            return "Ticket #{$record->recurrable->id}";
                        }

                        return class_basename($record->recurrable_type);
                    })
                    ->badge()
                    ->color('info')
                    ->placeholder('None')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('last_processed_at')
                    ->label('Last Run')
                    ->since()
                    ->placeholder('Never')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'income' => 'Income',
                        'expense' => 'Expense',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('All transactions')
                    ->trueLabel('Active only')
                    ->falseLabel('Paused only'),

                Tables\Filters\SelectFilter::make('frequency')
                    ->options([
                        'daily' => 'Daily',
                        'weekly' => 'Weekly',
                        'monthly' => 'Monthly',
                        'quarterly' => 'Quarterly',
                        'yearly' => 'Yearly',
                    ]),

                Tables\Filters\Filter::make('due_soon')
                    ->query(fn ($query) => $query->where('is_active', true)
                        ->where('next_due_date', '<=', now()->addDays(7)))
                    ->label('Due within 7 days'),

                Tables\Filters\Filter::make('overdue')
                    ->query(fn ($query) => $query->where('is_active', true)
                        ->where('next_due_date', '<', now()))
                    ->label('Overdue'),
            ])
            ->actions([
                Tables\Actions\Action::make('process_now')
                    ->label('Process Now')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->visible(fn (RecurringTransaction $record): bool => $record->is_active && $record->next_due_date->lte(now())
                    )
                    ->action(function (RecurringTransaction $record): void {
                        $transaction = $record->createTransaction();
                        \Filament\Notifications\Notification::make()
                            ->title('Transaction Created')
                            ->body("Created transaction #{$transaction->reference_number} for {$record->formatted_amount}")
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Process Recurring Transaction')
                    ->modalDescription('This will create a new transaction and update the next due date.'),

                Tables\Actions\Action::make('pause')
                    ->label('Pause')
                    ->icon('heroicon-o-pause')
                    ->color('warning')
                    ->visible(fn (RecurringTransaction $record): bool => $record->is_active)
                    ->action(function (RecurringTransaction $record): void {
                        $record->pause();
                    })
                    ->requiresConfirmation(),

                Tables\Actions\Action::make('resume')
                    ->label('Resume')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->visible(fn (RecurringTransaction $record): bool => ! $record->is_active)
                    ->action(function (RecurringTransaction $record): void {
                        $record->resume();
                    })
                    ->requiresConfirmation(),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('pause_selected')
                        ->label('Pause Selected')
                        ->icon('heroicon-o-pause')
                        ->color('warning')
                        ->action(function ($records): void {
                            foreach ($records as $record) {
                                if ($record->is_active) {
                                    $record->pause();
                                }
                            }
                        })
                        ->requiresConfirmation(),

                    Tables\Actions\BulkAction::make('resume_selected')
                        ->label('Resume Selected')
                        ->icon('heroicon-o-play')
                        ->color('success')
                        ->action(function ($records): void {
                            foreach ($records as $record) {
                                if (! $record->is_active) {
                                    $record->resume();
                                }
                            }
                        })
                        ->requiresConfirmation(),

                    Tables\Actions\BulkAction::make('process_due')
                        ->label('Process Due Transactions')
                        ->icon('heroicon-o-play-circle')
                        ->color('success')
                        ->action(function ($records): void {
                            $processed = 0;
                            foreach ($records as $record) {
                                if ($record->is_active && $record->next_due_date->lte(now())) {
                                    $record->createTransaction();
                                    $processed++;
                                }
                            }

                            \Filament\Notifications\Notification::make()
                                ->title('Bulk Processing Complete')
                                ->body("Processed {$processed} transactions")
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Process Due Transactions')
                        ->modalDescription('This will process all selected transactions that are due.'),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('next_due_date', 'asc')
            ->emptyStateHeading('No recurring transactions set up')
            ->emptyStateDescription('Create recurring transactions for automated billing and expense tracking.')
            ->emptyStateIcon('heroicon-o-arrow-path');
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
            'index' => Pages\ListRecurringTransactions::route('/'),
            'create' => Pages\CreateRecurringTransaction::route('/create'),
            'edit' => Pages\EditRecurringTransaction::route('/{record}/edit'),
        ];
    }
}
