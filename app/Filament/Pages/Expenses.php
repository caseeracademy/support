<?php

namespace App\Filament\Pages;

use App\Models\Category;
use App\Models\PaymentMethod;
use App\Models\Transaction;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Spatie\LaravelPdf\Facades\Pdf;

class Expenses extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-trending-down';

    protected static ?string $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.expenses';

    protected static ?string $navigationLabel = 'Expenses';

    public ?array $data = [];

    public $start_date;

    public $end_date;

    public $category_id;

    public $payment_method_id;

    public function mount(): void
    {
        $this->start_date = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->end_date = Carbon::now()->endOfMonth()->format('Y-m-d');
    }

    public static function canAccess(): bool
    {
        return Auth::user()?->hasRole('admin') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Filter Expenses')
                    ->description('Filter expenses by date range, category, or payment method')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                DatePicker::make('start_date')
                                    ->label('Start Date')
                                    ->required()
                                    ->default(Carbon::now()->startOfMonth())
                                    ->maxDate(now())
                                    ->reactive(),

                                DatePicker::make('end_date')
                                    ->label('End Date')
                                    ->required()
                                    ->default(Carbon::now()->endOfMonth())
                                    ->afterOrEqual('start_date')
                                    ->maxDate(now())
                                    ->reactive(),
                            ]),

                        Grid::make(2)
                            ->schema([
                                Select::make('category_id')
                                    ->label('Category')
                                    ->options(Category::where('type', 'expense')->active()->pluck('name', 'id'))
                                    ->placeholder('All Categories')
                                    ->searchable()
                                    ->reactive(),

                                Select::make('payment_method_id')
                                    ->label('Payment Method')
                                    ->options(PaymentMethod::pluck('name', 'id'))
                                    ->placeholder('All Payment Methods')
                                    ->searchable()
                                    ->reactive(),
                            ]),
                    ])
                    ->collapsible()
                    ->persistCollapsed(),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('applyFilters')
                ->label('Apply Filters')
                ->icon('heroicon-o-funnel')
                ->color('primary')
                ->action(function () {
                    $this->data = $this->form->getState();
                    $this->start_date = $this->data['start_date'] ?? $this->start_date;
                    $this->end_date = $this->data['end_date'] ?? $this->end_date;
                    $this->category_id = $this->data['category_id'] ?? null;
                    $this->payment_method_id = $this->data['payment_method_id'] ?? null;
                    $this->resetTable();
                }),

            Action::make('exportPdf')
                ->label('Export to PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action('exportToPdf'),

            Action::make('thisMonth')
                ->label('This Month')
                ->icon('heroicon-o-calendar')
                ->color('gray')
                ->action(function () {
                    $this->start_date = Carbon::now()->startOfMonth()->format('Y-m-d');
                    $this->end_date = Carbon::now()->endOfMonth()->format('Y-m-d');
                    $this->category_id = null;
                    $this->payment_method_id = null;
                    $this->resetTable();
                }),

            Action::make('lastMonth')
                ->label('Last Month')
                ->icon('heroicon-o-calendar')
                ->color('gray')
                ->action(function () {
                    $this->start_date = Carbon::now()->subMonth()->startOfMonth()->format('Y-m-d');
                    $this->end_date = Carbon::now()->subMonth()->endOfMonth()->format('Y-m-d');
                    $this->category_id = null;
                    $this->payment_method_id = null;
                    $this->resetTable();
                }),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getFilteredExpensesQuery())
            ->columns([
                Tables\Columns\TextColumn::make('transaction_date')
                    ->label('Date')
                    ->date()
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->limit(40)
                    ->weight(FontWeight::Medium),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category')
                    ->sortable()
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('paymentMethod.name')
                    ->label('Payment Method')
                    ->sortable()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->money('USD', divideBy: false)
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    ->color('danger'),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'completed',
                        'danger' => 'cancelled',
                    ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Category')
                    ->options(Category::where('type', 'expense')->active()->pluck('name', 'id'))
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'],
                            fn (Builder $query, $value): Builder => $query->where('category_id', $value)
                        );
                    }),

                Tables\Filters\SelectFilter::make('payment_method_id')
                    ->label('Payment Method')
                    ->options(PaymentMethod::pluck('name', 'id'))
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'],
                            fn (Builder $query, $value): Builder => $query->where('payment_method_id', $value)
                        );
                    }),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->defaultSort('transaction_date', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn (Transaction $record): string => \App\Filament\Resources\TransactionResource::getUrl('view', ['record' => $record])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    protected function getFilteredExpensesQuery(): Builder
    {
        $startDate = $this->start_date ? Carbon::parse($this->start_date) : Carbon::now()->startOfMonth();
        $endDate = $this->end_date ? Carbon::parse($this->end_date) : Carbon::now()->endOfMonth();

        return Transaction::query()
            ->where('type', 'expense')
            ->whereBetween('transaction_date', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->when($this->category_id, fn ($query) => $query->where('category_id', $this->category_id))
            ->when($this->payment_method_id, fn ($query) => $query->where('payment_method_id', $this->payment_method_id))
            ->with(['category', 'paymentMethod']);
    }

    public function exportToPdf()
    {
        try {
            $startDate = $this->start_date ? Carbon::parse($this->start_date) : Carbon::now()->startOfMonth();
            $endDate = $this->end_date ? Carbon::parse($this->end_date) : Carbon::now()->endOfMonth();

            $expenses = $this->getFilteredExpensesQuery()->get();

            $totalAmount = $expenses->sum('amount');

            $data = [
                'expenses' => $expenses,
                'start_date' => $startDate->format('F j, Y'),
                'end_date' => $endDate->format('F j, Y'),
                'total_amount' => $totalAmount,
                'count' => $expenses->count(),
                'filters' => [
                    'category' => $this->category_id ? Category::find($this->category_id)?->name : null,
                    'payment_method' => $this->payment_method_id ? PaymentMethod::find($this->payment_method_id)?->name : null,
                ],
            ];

            $pdf = Pdf::view('financial.reports.expenses', $data)
                ->format('a4')
                ->margins(10, 10, 10, 10)
                ->headerView('financial.reports.header')
                ->footerView('financial.reports.footer');

            $filename = 'expenses-'.$startDate->format('Y-m-d').'-to-'.$endDate->format('Y-m-d').'.pdf';

            return $pdf->download($filename);

        } catch (\Exception $e) {
            Notification::make()
                ->title('PDF Export Failed')
                ->body('Error: '.$e->getMessage())
                ->danger()
                ->send();
        }
    }
}
