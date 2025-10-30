<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\FinancialAnalyticsService;
use App\Services\FinancialReportService;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateMonthlyFinancialSummary implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;

    public int $maxExceptions = 3;

    public function __construct(
        protected ?int $month = null,
        protected ?int $year = null,
        protected ?int $notifyUserId = null
    ) {
        $this->onQueue('reports');
    }

    public function handle(): void
    {
        try {
            $month = $this->month ?? now()->subMonth()->month;
            $year = $this->year ?? now()->subMonth()->year;

            Log::info('Generating monthly financial summary', [
                'month' => $month,
                'year' => $year,
            ]);

            $startDate = Carbon::create($year, $month, 1)->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();

            // Generate comprehensive summary
            $summary = $this->generateComprehensiveSummary($startDate, $endDate);

            // Store summary in database or file system
            $this->storeSummary($summary, $month, $year);

            // Send notifications to relevant users
            $this->sendNotifications($summary, $month, $year);

            Log::info('Monthly financial summary generated successfully', [
                'month' => $month,
                'year' => $year,
                'summary' => $summary['key_metrics'],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to generate monthly financial summary', [
                'month' => $this->month,
                'year' => $this->year,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    protected function generateComprehensiveSummary(Carbon $startDate, Carbon $endDate): array
    {
        $reportService = app(FinancialReportService::class);
        $analyticsService = app(FinancialAnalyticsService::class);

        $profitLoss = $reportService->generateProfitLossStatement($startDate, $endDate);
        $cashFlow = $reportService->generateCashFlowStatement($startDate, $endDate);

        return [
            'period' => [
                'month' => $startDate->format('F Y'),
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ],
            'key_metrics' => [
                'total_income' => $profitLoss['summary']['total_income'],
                'total_expenses' => $profitLoss['summary']['total_expenses'],
                'net_profit' => $profitLoss['summary']['net_profit'],
                'profit_margin' => $profitLoss['summary']['profit_margin'],
                'is_profitable' => $profitLoss['summary']['is_profitable'],
                'cash_flow_change' => $cashFlow['summary']['net_change'],
                'formatted_income' => $profitLoss['summary']['formatted_income'],
                'formatted_expenses' => $profitLoss['summary']['formatted_expenses'],
                'formatted_profit' => $profitLoss['summary']['formatted_profit'],
            ],
            'top_income_categories' => array_slice($profitLoss['income']['categories'], 0, 5),
            'top_expense_categories' => array_slice($profitLoss['expenses']['categories'], 0, 5),
            'generated_at' => now()->toDateTimeString(),
        ];
    }

    protected function storeSummary(array $summary, int $month, int $year): void
    {
        $filename = "financial-summary-{$year}-".str_pad($month, 2, '0', STR_PAD_LEFT).'.json';
        $path = storage_path("app/financial-summaries/{$filename}");

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        file_put_contents($path, json_encode($summary, JSON_PRETTY_PRINT));

        Log::info('Stored monthly financial summary', ['path' => $path]);
    }

    protected function sendNotifications(array $summary, int $month, int $year): void
    {
        $monthName = Carbon::create($year, $month, 1)->format('F Y');
        $metrics = $summary['key_metrics'];

        $notification = Notification::make()
            ->title("Monthly Financial Summary - {$monthName}")
            ->body($this->formatNotificationBody($metrics))
            ->icon('heroicon-o-chart-bar')
            ->color($metrics['is_profitable'] ? 'success' : 'warning')
            ->persistent()
            ->actions([
                \Filament\Notifications\Actions\Action::make('view_details')
                    ->label('View Details')
                    ->url('/admin/financial-reports')
                    ->button(),
            ]);

        // Send to specific user if requested
        if ($this->notifyUserId) {
            if ($user = User::find($this->notifyUserId)) {
                $user->notify($notification);
            }
        } else {
            // Send to all admin users
            User::chunk(10, function ($users) use ($notification) {
                foreach ($users as $user) {
                    $user->notify($notification);
                }
            });
        }
    }

    protected function formatNotificationBody(array $metrics): string
    {
        $lines = [];
        $lines[] = "Income: {$metrics['formatted_income']}";
        $lines[] = "Expenses: {$metrics['formatted_expenses']}";
        $lines[] = "Net Profit: {$metrics['formatted_profit']} ({$metrics['profit_margin']}% margin)";

        if ($metrics['is_profitable']) {
            $lines[] = '✓ Profitable month';
        } else {
            $lines[] = '⚠ Loss recorded';
        }

        return implode("\n", $lines);
    }

    public static function scheduleForAllUsers(): void
    {
        User::chunk(10, function ($users) {
            foreach ($users as $user) {
                static::dispatch(null, null, $user->id);
            }
        });
    }

    public static function scheduleMonthlyRun(): void
    {
        // Run on the 1st of each month for previous month
        static::dispatch()->delay(now()->startOfMonth()->addDay());
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Monthly financial summary job failed permanently', [
            'month' => $this->month,
            'year' => $this->year,
            'notify_user_id' => $this->notifyUserId,
            'exception' => $exception->getMessage(),
        ]);

        if ($this->notifyUserId) {
            if ($user = User::find($this->notifyUserId)) {
                Notification::make()
                    ->title('Financial Summary Generation Failed')
                    ->body('Failed to generate monthly financial summary: '.$exception->getMessage())
                    ->danger()
                    ->sendToDatabase($user);
            }
        }
    }
}




