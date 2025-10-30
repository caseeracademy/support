<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\Ticket;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessInvoiceAutomation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:process-automation {--dry-run : Show what would be processed without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process automatic invoice actions like status updates, payment matching, and reminder scheduling';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $this->info('Starting invoice automation processing...');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        $processedCount = 0;
        $errorCount = 0;

        try {
            // Process overdue status updates
            $overdueCount = $this->processOverdueInvoices($dryRun);
            $processedCount += $overdueCount;

            // Process automatic payment matching
            $matchedCount = $this->processPaymentMatching($dryRun);
            $processedCount += $matchedCount;

            // Process reminder scheduling
            $remindersCount = $this->processReminderScheduling($dryRun);
            $processedCount += $remindersCount;

            // Auto-generate invoices from completed tickets
            $generatedCount = $this->processAutoInvoiceGeneration($dryRun);
            $processedCount += $generatedCount;

        } catch (\Exception $e) {
            $this->error("Error during automation processing: {$e->getMessage()}");
            Log::error('Invoice automation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $errorCount++;
        }

        $this->newLine();
        $this->info('Automation processing completed!');
        $this->info("Total actions processed: {$processedCount}");

        if ($errorCount > 0) {
            $this->error("Errors encountered: {$errorCount}");

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    protected function processOverdueInvoices(bool $dryRun): int
    {
        $this->info('Processing overdue status updates...');

        $overdueInvoices = Invoice::where('status', 'sent')
            ->where('due_date', '<', now())
            ->get();

        if ($overdueInvoices->isEmpty()) {
            $this->info('  No invoices to mark as overdue');

            return 0;
        }

        $count = $overdueInvoices->count();

        if ($dryRun) {
            $this->info("  Would mark {$count} invoices as overdue");
            foreach ($overdueInvoices as $invoice) {
                $this->line("    - Invoice {$invoice->invoice_number} (due: {$invoice->due_date->format('Y-m-d')})");
            }
        } else {
            foreach ($overdueInvoices as $invoice) {
                $invoice->update(['status' => 'overdue']);
                $this->line("  ✓ Marked invoice {$invoice->invoice_number} as overdue");
            }
        }

        return $count;
    }

    protected function processPaymentMatching(bool $dryRun): int
    {
        $this->info('Processing automatic payment matching...');

        $unpaidInvoices = Invoice::whereIn('status', ['sent', 'overdue'])
            ->where('remaining_amount', '>', 0)
            ->where('invoice_date', '>=', now()->subMonths(3))
            ->with('customer')
            ->get();

        $matchedCount = 0;

        foreach ($unpaidInvoices as $invoice) {
            if ($dryRun) {
                // Just check if there are potential matches
                $potentialMatches = $this->findPotentialPaymentMatches($invoice);
                if ($potentialMatches->isNotEmpty()) {
                    $this->line("  Would match payment for invoice {$invoice->invoice_number}");
                    $matchedCount++;
                }
            } else {
                if ($invoice->autoMatchPayments()) {
                    $this->line("  ✓ Matched payment for invoice {$invoice->invoice_number}");
                    $matchedCount++;
                }
            }
        }

        if ($matchedCount === 0) {
            $this->info('  No automatic payment matches found');
        }

        return $matchedCount;
    }

    protected function processReminderScheduling(bool $dryRun): int
    {
        $this->info('Processing reminder scheduling...');

        $invoicesNeedingReminders = Invoice::where('status', 'sent')
            ->where('due_date', '>=', now())
            ->whereDoesntHave('jobs', function ($query) {
                $query->where('queue', 'notifications')
                    ->where('available_at', '>', now());
            })
            ->get();

        $count = 0;

        foreach ($invoicesNeedingReminders as $invoice) {
            if ($dryRun) {
                $this->line("  Would schedule reminders for invoice {$invoice->invoice_number}");
            } else {
                $invoice->scheduleAutomaticReminders();
                $this->line("  ✓ Scheduled reminders for invoice {$invoice->invoice_number}");
            }
            $count++;
        }

        if ($count === 0) {
            $this->info('  No invoices need reminder scheduling');
        }

        return $count;
    }

    protected function processAutoInvoiceGeneration(bool $dryRun): int
    {
        $this->info('Processing auto-invoice generation from tickets...');

        // Find completed tickets without invoices that have payment amounts
        $eligibleTickets = Ticket::where('status', 'resolved')
            ->whereNotNull('total_amount')
            ->where('total_amount', '>', 0)
            ->whereDoesntHave('invoices')
            ->where('updated_at', '>=', now()->subDays(7)) // Only recent tickets
            ->get();

        $count = 0;

        foreach ($eligibleTickets as $ticket) {
            if ($dryRun) {
                $this->line("  Would generate invoice for ticket #{$ticket->id} ({$ticket->subject})");
            } else {
                Invoice::autoGenerateFromTicket($ticket->id);
                $this->line("  ✓ Queued invoice generation for ticket #{$ticket->id}");
            }
            $count++;
        }

        if ($count === 0) {
            $this->info('  No tickets eligible for auto-invoice generation');
        }

        return $count;
    }

    protected function findPotentialPaymentMatches(Invoice $invoice)
    {
        if (! $invoice->customer) {
            return collect();
        }

        return $invoice->customer->transactions()
            ->where('type', 'income')
            ->where('status', 'completed')
            ->where('amount', '>=', $invoice->remaining_amount)
            ->whereDate('transaction_date', '>=', $invoice->invoice_date)
            ->whereNull('transactionable_id')
            ->get();
    }
}
