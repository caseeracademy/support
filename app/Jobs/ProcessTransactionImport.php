<?php

namespace App\Jobs;

use App\Imports\TransactionImport;
use App\Models\User;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ProcessTransactionImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300; // 5 minutes

    public int $maxExceptions = 3;

    public function __construct(
        protected string $filePath,
        protected int $userId,
        protected array $importOptions = []
    ) {
        $this->onQueue('imports');
    }

    public function handle(): void
    {
        try {
            Log::info('Starting transaction import', [
                'file_path' => $this->filePath,
                'user_id' => $this->userId,
                'options' => $this->importOptions,
            ]);

            // Verify file exists
            if (! Storage::disk('local')->exists($this->filePath)) {
                throw new \Exception("Import file not found: {$this->filePath}");
            }

            // Get the user who initiated the import
            $user = User::find($this->userId);
            if (! $user) {
                throw new \Exception("User not found: {$this->userId}");
            }

            // Send starting notification
            $this->sendNotification($user, 'started');

            // Create import instance
            $import = new TransactionImport;

            // Process the import
            Excel::import($import, $this->filePath, 'local');

            // Get import summary
            $summary = $import->getSummary();

            Log::info('Transaction import completed', [
                'user_id' => $this->userId,
                'summary' => $summary,
            ]);

            // Send completion notification
            $this->sendNotification($user, 'completed', $summary);

            // Clean up the uploaded file
            $this->cleanup();

        } catch (\Exception $e) {
            Log::error('Transaction import failed', [
                'file_path' => $this->filePath,
                'user_id' => $this->userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Send failure notification
            if ($user = User::find($this->userId)) {
                $this->sendNotification($user, 'failed', ['error' => $e->getMessage()]);
            }

            // Clean up the uploaded file
            $this->cleanup();

            throw $e;
        }
    }

    protected function sendNotification(User $user, string $status, array $data = []): void
    {
        $notification = match ($status) {
            'started' => $this->createStartedNotification(),
            'completed' => $this->createCompletedNotification($data),
            'failed' => $this->createFailedNotification($data),
            default => null,
        };

        if ($notification) {
            $user->notify($notification);
        }
    }

    protected function createStartedNotification(): Notification
    {
        return Notification::make()
            ->title('Transaction Import Started')
            ->body('Your CSV file is being processed. You will be notified when complete.')
            ->icon('heroicon-o-arrow-up-tray')
            ->color('info')
            ->persistent()
            ->actions([
                Action::make('view_transactions')
                    ->label('View Transactions')
                    ->url('/admin/transactions')
                    ->button(),
            ]);
    }

    protected function createCompletedNotification(array $summary): Notification
    {
        $successful = $summary['successful_imports'] ?? 0;
        $errors = $summary['errors_count'] ?? 0;
        $hasErrors = $summary['has_errors'] ?? false;

        $body = "Successfully imported {$successful} transactions.";
        if ($hasErrors) {
            $body .= " {$errors} rows had errors and were skipped.";
        }

        $notification = Notification::make()
            ->title('Transaction Import Completed')
            ->body($body)
            ->icon('heroicon-o-check-circle')
            ->color($hasErrors ? 'warning' : 'success')
            ->persistent()
            ->actions([
                Action::make('view_transactions')
                    ->label('View Transactions')
                    ->url('/admin/transactions')
                    ->button(),
            ]);

        // Add error details if there are any
        if ($hasErrors && ! empty($summary['errors'])) {
            $errorDetails = collect($summary['errors'])->take(5)->implode("\n");
            if (count($summary['errors']) > 5) {
                $errorDetails .= "\n... and ".(count($summary['errors']) - 5).' more errors';
            }

            $notification->body($body."\n\nFirst few errors:\n".$errorDetails);
        }

        return $notification;
    }

    protected function createFailedNotification(array $data): Notification
    {
        return Notification::make()
            ->title('Transaction Import Failed')
            ->body('There was an error processing your CSV file: '.($data['error'] ?? 'Unknown error'))
            ->icon('heroicon-o-exclamation-triangle')
            ->color('danger')
            ->persistent()
            ->actions([
                Action::make('try_again')
                    ->label('Try Again')
                    ->url('/admin/transactions/import')
                    ->button(),

                Action::make('contact_support')
                    ->label('Contact Support')
                    ->url('/admin/tickets/create')
                    ->button()
                    ->color('gray'),
            ]);
    }

    protected function cleanup(): void
    {
        try {
            if (Storage::disk('local')->exists($this->filePath)) {
                Storage::disk('local')->delete($this->filePath);
                Log::info('Cleaned up import file', ['file_path' => $this->filePath]);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to clean up import file', [
                'file_path' => $this->filePath,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Transaction import job failed permanently', [
            'file_path' => $this->filePath,
            'user_id' => $this->userId,
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Send failure notification
        if ($user = User::find($this->userId)) {
            $this->sendNotification($user, 'failed', ['error' => $exception->getMessage()]);
        }

        // Clean up
        $this->cleanup();
    }

    public function retryUntil(): \DateTime
    {
        return now()->addMinutes(30);
    }

    public function backoff(): array
    {
        return [30, 60, 120]; // Retry after 30s, 1m, 2m
    }
}




