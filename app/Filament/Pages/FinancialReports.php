<?php

namespace App\Filament\Pages;

use App\Exports\TransactionExport;
use App\Models\Category;
use App\Models\Customer;
use App\Models\PaymentMethod;
use App\Services\FinancialReportService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class FinancialReports extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 6;

    protected static string $view = 'filament.pages.financial-reports';

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user?->hasRole(['admin']) === true;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public ?array $profitLossData = [];

    public ?array $cashFlowData = [];

    public ?array $transactionSummaryData = [];

    // Form fields for report generation
    public $start_date;

    public $end_date;

    public $type;

    public $category_id;

    public $payment_method_id;

    public $customer_id;

    public $period = 'monthly';

    public function mount(): void
    {
        // Set default date range to current month
        $this->start_date = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->end_date = Carbon::now()->endOfMonth()->format('Y-m-d');

        // Load initial data
        $this->generateReports();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Report Parameters')
                    ->description('Configure the date range and filters for your financial reports')
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

                        Grid::make(3)
                            ->schema([
                                Select::make('type')
                                    ->label('Transaction Type')
                                    ->options([
                                        'income' => 'Income Only',
                                        'expense' => 'Expenses Only',
                                    ])
                                    ->placeholder('All Types')
                                    ->reactive(),

                                Select::make('category_id')
                                    ->label('Category')
                                    ->options(Category::active()->pluck('name', 'id'))
                                    ->placeholder('All Categories')
                                    ->searchable()
                                    ->reactive(),

                                Select::make('payment_method_id')
                                    ->label('Payment Method')
                                    ->options(PaymentMethod::pluck('name', 'id'))
                                    ->placeholder('All Methods')
                                    ->searchable()
                                    ->reactive(),
                            ]),

                        Grid::make(2)
                            ->schema([
                                Select::make('customer_id')
                                    ->label('Customer (for payment history)')
                                    ->options(Customer::pluck('name', 'id'))
                                    ->placeholder('All Customers')
                                    ->searchable()
                                    ->reactive(),

                                Select::make('period')
                                    ->label('Trend Analysis Period')
                                    ->options([
                                        'daily' => 'Daily',
                                        'weekly' => 'Weekly',
                                        'monthly' => 'Monthly',
                                    ])
                                    ->default('monthly')
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
            Action::make('generateReports')
                ->label('Generate Reports')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->action('generateReports'),

            Action::make('exportExcel')
                ->label('Export to Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action('exportToExcel'),

            Action::make('thisMonth')
                ->label('This Month')
                ->icon('heroicon-o-calendar')
                ->color('gray')
                ->action(function () {
                    $this->start_date = Carbon::now()->startOfMonth()->format('Y-m-d');
                    $this->end_date = Carbon::now()->endOfMonth()->format('Y-m-d');
                    $this->generateReports();
                }),

            Action::make('lastMonth')
                ->label('Last Month')
                ->icon('heroicon-o-calendar')
                ->color('gray')
                ->action(function () {
                    $this->start_date = Carbon::now()->subMonth()->startOfMonth()->format('Y-m-d');
                    $this->end_date = Carbon::now()->subMonth()->endOfMonth()->format('Y-m-d');
                    $this->generateReports();
                }),

            Action::make('thisYear')
                ->label('This Year')
                ->icon('heroicon-o-calendar')
                ->color('gray')
                ->action(function () {
                    $this->start_date = Carbon::now()->startOfYear()->format('Y-m-d');
                    $this->end_date = Carbon::now()->endOfYear()->format('Y-m-d');
                    $this->generateReports();
                }),
        ];
    }

    public function generateReports(): void
    {
        try {
            $reportService = app(FinancialReportService::class);
            $startDate = Carbon::parse($this->start_date);
            $endDate = Carbon::parse($this->end_date);

            // Generate all reports
            $this->profitLossData = $reportService->generateProfitLossStatement($startDate, $endDate);
            $this->cashFlowData = $reportService->generateCashFlowStatement($startDate, $endDate);
            $this->transactionSummaryData = $reportService->generateTransactionSummary(
                $startDate,
                $endDate,
                $this->type,
                $this->category_id,
                $this->payment_method_id
            );

            Notification::make()
                ->title('Reports Generated Successfully')
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error Generating Reports')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function exportToExcel()
    {
        try {
            $startDate = Carbon::parse($this->start_date);
            $endDate = Carbon::parse($this->end_date);

            $filename = 'financial-report-'.$startDate->format('Y-m-d').'-to-'.$endDate->format('Y-m-d').'.xlsx';

            return Excel::download(
                new TransactionExport(
                    $startDate,
                    $endDate,
                    $this->type,
                    $this->category_id,
                    $this->payment_method_id
                ),
                $filename
            );

        } catch (\Exception $e) {
            Notification::make()
                ->title('Export Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function downloadProfitLossPdf(): void
    {
        $params = [
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
        ];

        $this->redirect(route('financial.reports.profit-loss', $params));
    }

    public function downloadCashFlowPdf(): void
    {
        $params = [
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
        ];

        $this->redirect(route('financial.reports.cash-flow', $params));
    }

    public function downloadTransactionSummaryPdf(): void
    {
        $params = array_filter([
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'type' => $this->type,
            'category_id' => $this->category_id,
            'payment_method_id' => $this->payment_method_id,
        ]);

        $this->redirect(route('financial.reports.transaction-summary', $params));
    }

    public function downloadCustomerPaymentsPdf(): void
    {
        $params = array_filter([
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'customer_id' => $this->customer_id,
        ]);

        $this->redirect(route('financial.reports.customer-payments', $params));
    }

    public function downloadTrendAnalysisPdf(): void
    {
        $params = [
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'period' => $this->period,
        ];

        $this->redirect(route('financial.reports.trend-analysis', $params));
    }
}
