<?php

namespace App\Console\Commands;

use App\Models\RecurringTransaction;
use Illuminate\Console\Command;

class ProcessRecurringTransactions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transactions:process-recurring 
                           {--dry-run : Show what would be processed without actually creating transactions}
                           {--force : Process transactions even if they are not due yet}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process due recurring transactions and create new transaction records';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔄 Processing recurring transactions...');
        
        $isDryRun = $this->option('dry-run');
        $isForced = $this->option('force');
        
        // Get due recurring transactions
        $query = RecurringTransaction::query()
            ->where('is_active', true)
            ->with(['category', 'paymentMethod', 'recurrable']);
            
        if (!$isForced) {
            $query->where('next_due_date', '<=', now());
        }
        
        $dueTransactions = $query->get();
        
        if ($dueTransactions->isEmpty()) {
            $this->info('✅ No recurring transactions are due for processing.');
            return self::SUCCESS;
        }
        
        $this->info("Found {$dueTransactions->count()} recurring transactions to process:");
        $this->newLine();
        
        $processedCount = 0;
        $skippedCount = 0;
        $errorCount = 0;
        
        $headers = ['ID', 'Title', 'Amount', 'Type', 'Next Due', 'Status'];
        $rows = [];
        
        foreach ($dueTransactions as $recurringTransaction) {
            $shouldProcess = $isForced || $recurringTransaction->next_due_date->lte(now());
            $status = 'Pending';
            
            if ($recurringTransaction->shouldStop()) {
                $status = 'Stopped (max reached)';
                $skippedCount++;
            } elseif (!$shouldProcess) {
                $status = 'Not due yet';
                $skippedCount++;
            } else {
                if ($isDryRun) {
                    $status = 'Would process';
                } else {
                    try {
                        $transaction = $recurringTransaction->createTransaction();
                        $status = "✅ Created #{$transaction->reference_number}";
                        $processedCount++;
                    } catch (\Exception $e) {
                        $status = "❌ Error: " . $e->getMessage();
                        $errorCount++;
                        $this->error("Failed to process recurring transaction #{$recurringTransaction->id}: " . $e->getMessage());
                    }
                }
            }
            
            $rows[] = [
                $recurringTransaction->id,
                \Str::limit($recurringTransaction->title, 30),
                $recurringTransaction->formatted_amount,
                ucfirst($recurringTransaction->type),
                $recurringTransaction->next_due_date->format('M j, Y'),
                $status,
            ];
        }
        
        $this->table($headers, $rows);
        $this->newLine();
        
        // Summary
        if ($isDryRun) {
            $this->warn("🔍 DRY RUN MODE - No transactions were actually created");
            $this->info("Would process: {$dueTransactions->count()} transactions");
        } else {
            $this->info("📊 Processing Summary:");
            $this->info("✅ Processed: {$processedCount}");
            $this->info("⏭️  Skipped: {$skippedCount}");
            
            if ($errorCount > 0) {
                $this->error("❌ Errors: {$errorCount}");
            }
            
            // Mark inactive any transactions that have reached their limits
            $stoppedCount = 0;
            foreach ($dueTransactions as $recurringTransaction) {
                if ($recurringTransaction->shouldStop() && $recurringTransaction->is_active) {
                    $recurringTransaction->update(['is_active' => false]);
                    $stoppedCount++;
                }
            }
            
            if ($stoppedCount > 0) {
                $this->info("🛑 Stopped {$stoppedCount} recurring transactions that reached their limits");
            }
        }
        
        return $errorCount > 0 ? self::FAILURE : self::SUCCESS;
    }
}
