<?php

namespace App\Filament\Resources;

use App\Exports\TransactionExport;
use App\Filament\Resources\TransactionResource\Pages;
use App\Imports\TransactionImport;
use App\Jobs\ProcessTransactionImport;
use App\Models\Customer;
use App\Models\Ticket;
use App\Models\Transaction;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'title';

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()?->hasRole('admin') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Transaction Details')
                    ->description('Basic transaction information')
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
                            ->placeholder('Brief description of the transaction'),

                        Forms\Components\Textarea::make('description')
                            ->placeholder('Detailed description (optional)')
                            ->maxLength(65535)
                            ->columnSpanFull(),

                        Forms\Components\DatePicker::make('transaction_date')
                            ->required()
                            ->default(now())
                            ->maxDate(now()),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Amount & Payment')
                    ->description('Financial details')
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
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Select::make('type')
                                    ->options([
                                        'income' => 'Income',
                                        'expense' => 'Expense',
                                    ])
                                    ->required(),
                                Forms\Components\TextInput::make('color')
                                    ->default('#3B82F6'),
                            ]),

                        Forms\Components\Select::make('payment_method_id')
                            ->relationship('paymentMethod', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Select::make('type')
                                    ->options([
                                        'cash' => 'Cash',
                                        'bank_transfer' => 'Bank Transfer',
                                        'card' => 'Card',
                                        'digital_wallet' => 'Digital Wallet',
                                        'cryptocurrency' => 'Cryptocurrency',
                                        'other' => 'Other',
                                    ])
                                    ->required(),
                            ]),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Status & References')
                    ->description('Transaction status and reference information')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled',
                                'refunded' => 'Refunded',
                            ])
                            ->default('completed')
                            ->required(),

                        Forms\Components\TextInput::make('external_reference')
                            ->placeholder('External payment ID or reference')
                            ->maxLength(255),

                        Forms\Components\DateTimePicker::make('processed_at')
                            ->default(fn () => now())
                            ->visible(fn (Forms\Get $get) => $get('status') === 'completed'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Link to Related Records')
                    ->description('Optional: Link this transaction to customers or tickets')
                    ->schema([
                        Forms\Components\Select::make('transactionable_type')
                            ->options([
                                Customer::class => 'Customer',
                                Ticket::class => 'Ticket',
                            ])
                            ->placeholder('Select related record type')
                            ->reactive()
                            ->afterStateUpdated(fn (callable $set) => $set('transactionable_id', null)),

                        Forms\Components\Select::make('transactionable_id')
                            ->placeholder('Select related record')
                            ->options(function (Forms\Get $get) {
                                $type = $get('transactionable_type');
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
                            ->visible(fn (Forms\Get $get) => filled($get('transactionable_type'))),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Hidden::make('created_by')
                    ->default(fn () => Auth::id()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->limit(30)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();

                        return strlen($state) > 30 ? $state : null;
                    })
                    ->url(fn (Transaction $record): string => TransactionResource::getUrl('view', ['record' => $record]))
                    ->weight(FontWeight::SemiBold)
                    ->color('primary'),

                Tables\Columns\BadgeColumn::make('type')
                    ->colors([
                        'success' => 'income',
                        'danger' => 'expense',
                    ])
                    ->icons([
                        'heroicon-o-arrow-trending-up' => 'income',
                        'heroicon-o-arrow-trending-down' => 'expense',
                    ]),

                Tables\Columns\TextColumn::make('formatted_amount')
                    ->label('Amount')
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('amount', $direction))
                    ->weight(FontWeight::SemiBold)
                    ->color(fn (Transaction $record): string => match ($record->type) {
                        'income' => 'success',
                        'expense' => 'danger',
                        default => 'primary',
                    }),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category')
                    ->sortable()
                    ->searchable()
                    ->badge(),

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
                        'refunded' => 'Refunded',
                    ]),

                Tables\Filters\SelectFilter::make('category_id')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('payment_method_id')
                    ->relationship('paymentMethod', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('amount_range')
                    ->form([
                        Forms\Components\TextInput::make('amount_from')
                            ->numeric()
                            ->placeholder('0.00'),
                        Forms\Components\TextInput::make('amount_to')
                            ->numeric()
                            ->placeholder('999999.99'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['amount_from'],
                                fn (Builder $query, $amount): Builder => $query->where('amount', '>=', $amount),
                            )
                            ->when(
                                $data['amount_to'],
                                fn (Builder $query, $amount): Builder => $query->where('amount', '<=', $amount),
                            );
                    }),

                Tables\Filters\Filter::make('transaction_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->placeholder('From date'),
                        Forms\Components\DatePicker::make('until')
                            ->placeholder('Until date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('transaction_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('transaction_date', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Transaction $record) => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->action(function (Transaction $record): void {
                        $record->approve(Auth::user());
                    }),
                Tables\Actions\Action::make('cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Transaction $record) => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->action(function (Transaction $record): void {
                        $record->cancel();
                    }),
            ])
            ->recordUrl(fn (Transaction $record): string => TransactionResource::getUrl('view', ['record' => $record]))
            ->headerActions([
                Tables\Actions\Action::make('import')
                    ->label('Import CSV')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('success')
                    ->form([
                        Forms\Components\FileUpload::make('csv_file')
                            ->label('CSV File')
                            ->acceptedFileTypes(['text/csv', '.csv'])
                            ->required()
                            ->helperText('Upload a CSV file with transaction data')
                            ->columnSpanFull(),

                        Forms\Components\Fieldset::make('CSV Format Guidelines')
                            ->schema([
                                Forms\Components\Placeholder::make('headers')
                                    ->content('Required columns: type, amount, title, date')
                                    ->columnSpanFull(),

                                Forms\Components\Placeholder::make('optional')
                                    ->content('Optional columns: currency, description, category, payment_method, status, reference')
                                    ->columnSpanFull(),

                                Forms\Components\Placeholder::make('formats')
                                    ->content('Date format: YYYY-MM-DD, Amount: numeric (no currency symbols), Type: income or expense')
                                    ->columnSpanFull(),
                            ]),

                        Forms\Components\Toggle::make('process_in_background')
                            ->label('Process in background')
                            ->helperText('For large files, process the import in the background and notify when complete')
                            ->default(true),
                    ])
                    ->action(function (array $data): void {
                        $file = $data['csv_file'];
                        $processInBackground = $data['process_in_background'] ?? true;

                        if ($processInBackground) {
                            // Store file and process in background
                            $path = Storage::disk('local')->putFile('imports', $file);

                            ProcessTransactionImport::dispatch($path, Auth::id());

                            Notification::make()
                                ->title('Import Started')
                                ->body('Your CSV file is being processed in the background. You will be notified when complete.')
                                ->success()
                                ->send();
                        } else {
                            // Process immediately
                            try {
                                $import = new TransactionImport;
                                Excel::import($import, $file);

                                $summary = $import->getSummary();

                                Notification::make()
                                    ->title('Import Completed')
                                    ->body("Successfully imported {$summary['successful_imports']} transactions".
                                           ($summary['has_errors'] ? " with {$summary['errors_count']} errors" : ''))
                                    ->success()
                                    ->send();

                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Import Failed')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }
                    }),

                Tables\Actions\Action::make('export')
                    ->label('Export CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->form([
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Start Date')
                            ->default(Carbon::now()->startOfMonth())
                            ->required(),

                        Forms\Components\DatePicker::make('end_date')
                            ->label('End Date')
                            ->default(Carbon::now()->endOfMonth())
                            ->required()
                            ->after('start_date'),

                        Forms\Components\Select::make('type')
                            ->label('Transaction Type')
                            ->options([
                                'income' => 'Income Only',
                                'expense' => 'Expenses Only',
                            ])
                            ->placeholder('All Types'),

                        Forms\Components\Select::make('category_id')
                            ->label('Category')
                            ->relationship('category', 'name')
                            ->placeholder('All Categories')
                            ->searchable(),

                        Forms\Components\Select::make('payment_method_id')
                            ->label('Payment Method')
                            ->relationship('paymentMethod', 'name')
                            ->placeholder('All Methods')
                            ->searchable(),
                    ])
                    ->action(function (array $data) {
                        $startDate = Carbon::parse($data['start_date']);
                        $endDate = Carbon::parse($data['end_date']);

                        $filename = 'transactions-'.$startDate->format('Y-m-d').'-to-'.$endDate->format('Y-m-d').'.xlsx';

                        return Excel::download(
                            new TransactionExport(
                                $startDate,
                                $endDate,
                                $data['type'] ?? null,
                                $data['category_id'] ?? null,
                                $data['payment_method_id'] ?? null
                            ),
                            $filename
                        );
                    }),

                Tables\Actions\Action::make('downloadTemplate')
                    ->label('Download Template')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('gray')
                    ->action(function () {
                        $headers = TransactionImport::getSampleHeaders();
                        $sampleData = TransactionImport::getSampleData();

                        // Create a simple CSV template
                        $csvContent = implode(',', $headers)."\n";
                        foreach ($sampleData as $row) {
                            $csvContent .= implode(',', array_values($row))."\n";
                        }

                        $filename = 'transaction-import-template.csv';
                        $tempPath = storage_path('app/temp/'.$filename);

                        // Ensure temp directory exists
                        if (! file_exists(dirname($tempPath))) {
                            mkdir(dirname($tempPath), 0755, true);
                        }

                        file_put_contents($tempPath, $csvContent);

                        return response()->download($tempPath, $filename)->deleteFileAfterSend();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('approve')
                        ->label('Approve Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records): void {
                            $user = Auth::user();
                            $count = 0;
                            foreach ($records as $record) {
                                if ($record->status === 'pending') {
                                    $record->approve($user);
                                    $count++;
                                }
                            }

                            Notification::make()
                                ->title("Approved {$count} transactions")
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('cancel')
                        ->label('Cancel Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function ($records): void {
                            $count = 0;
                            foreach ($records as $record) {
                                if ($record->status === 'pending') {
                                    $record->cancel();
                                    $count++;
                                }
                            }

                            Notification::make()
                                ->title("Cancelled {$count} transactions")
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('updateCategory')
                        ->label('Update Category')
                        ->icon('heroicon-o-tag')
                        ->color('warning')
                        ->form([
                            Forms\Components\Select::make('category_id')
                                ->label('New Category')
                                ->relationship('category', 'name')
                                ->required()
                                ->searchable()
                                ->createOptionForm([
                                    Forms\Components\TextInput::make('name')
                                        ->required()
                                        ->maxLength(255),
                                    Forms\Components\Select::make('type')
                                        ->options([
                                            'income' => 'Income',
                                            'expense' => 'Expense',
                                        ])
                                        ->required(),
                                    Forms\Components\TextInput::make('color')
                                        ->default('#3B82F6'),
                                ]),
                        ])
                        ->action(function (array $data, $records): void {
                            $categoryId = $data['category_id'];
                            $count = 0;
                            foreach ($records as $record) {
                                $record->update(['category_id' => $categoryId]);
                                $count++;
                            }

                            Notification::make()
                                ->title("Updated category for {$count} transactions")
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('updatePaymentMethod')
                        ->label('Update Payment Method')
                        ->icon('heroicon-o-credit-card')
                        ->color('info')
                        ->form([
                            Forms\Components\Select::make('payment_method_id')
                                ->label('New Payment Method')
                                ->relationship('paymentMethod', 'name')
                                ->required()
                                ->searchable()
                                ->createOptionForm([
                                    Forms\Components\TextInput::make('name')
                                        ->required()
                                        ->maxLength(255),
                                    Forms\Components\Select::make('type')
                                        ->options([
                                            'cash' => 'Cash',
                                            'bank_transfer' => 'Bank Transfer',
                                            'card' => 'Card',
                                            'digital_wallet' => 'Digital Wallet',
                                            'cryptocurrency' => 'Cryptocurrency',
                                            'other' => 'Other',
                                        ])
                                        ->required(),
                                ]),
                        ])
                        ->action(function (array $data, $records): void {
                            $paymentMethodId = $data['payment_method_id'];
                            $count = 0;
                            foreach ($records as $record) {
                                $record->update(['payment_method_id' => $paymentMethodId]);
                                $count++;
                            }

                            Notification::make()
                                ->title("Updated payment method for {$count} transactions")
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('updateStatus')
                        ->label('Update Status')
                        ->icon('heroicon-o-arrow-path')
                        ->color('gray')
                        ->form([
                            Forms\Components\Select::make('status')
                                ->label('New Status')
                                ->options([
                                    'pending' => 'Pending',
                                    'completed' => 'Completed',
                                    'cancelled' => 'Cancelled',
                                    'refunded' => 'Refunded',
                                ])
                                ->required(),
                        ])
                        ->action(function (array $data, $records): void {
                            $status = $data['status'];
                            $count = 0;
                            $user = Auth::user();

                            foreach ($records as $record) {
                                if ($status === 'completed' && $record->status === 'pending') {
                                    $record->approve($user);
                                } elseif ($status === 'cancelled') {
                                    $record->cancel();
                                } else {
                                    $record->update(['status' => $status]);
                                }
                                $count++;
                            }

                            Notification::make()
                                ->title("Updated status for {$count} transactions")
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('exportSelected')
                        ->label('Export Selected')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('success')
                        ->action(function ($records) {
                            $ids = $records->pluck('id')->toArray();
                            $filename = 'selected-transactions-'.now()->format('Y-m-d-H-i-s').'.xlsx';

                            // Create a custom export for selected transactions
                            $query = Transaction::whereIn('id', $ids)->with(['category', 'paymentMethod', 'createdBy']);

                            return Excel::download(new class($query) implements \Maatwebsite\Excel\Concerns\FromQuery, \Maatwebsite\Excel\Concerns\WithHeadings
                            {
                                public function __construct(private $query) {}

                                public function query()
                                {
                                    return $this->query;
                                }

                                public function headings(): array
                                {
                                    return [
                                        'ID', 'Reference', 'Date', 'Type', 'Title', 'Amount', 'Currency',
                                        'Category', 'Payment Method', 'Status', 'Created By',
                                    ];
                                }
                            }, $filename);
                        }),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('transaction_date', 'desc');
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
            'index' => Pages\ListTransactions::route('/'),
            'create' => Pages\CreateTransaction::route('/create'),
            'view' => Pages\ViewTransaction::route('/{record}'),
            'edit' => Pages\EditTransaction::route('/{record}/edit'),
        ];
    }
}
