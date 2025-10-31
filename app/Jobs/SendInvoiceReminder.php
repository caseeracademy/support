<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendInvoiceReminder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 60;

    public int $maxExceptions = 3;

    public function __construct(
        protected int $invoiceId,
        protected string $reminderType = 'standard', // 'due_soon', 'overdue', 'final'
        protected ?int $userId = null
    ) {
        $this->onQueue('notifications');
    }

    public function handle(): void
    {
        try {
            $invoice = Invoice::with(['customer', 'ticket', 'createdBy'])->find($this->invoiceId);

            if (! $invoice) {
                throw new \Exception("Invoice not found: {$this->invoiceId}");
            }

            // Check if invoice still requires reminder
            if (! $this->shouldSendReminder($invoice)) {
                Log::info('Invoice reminder skipped - no longer needed', [
                    'invoice_id' => $this->invoiceId,
                    'invoice_status' => $invoice->status,
                    'reminder_type' => $this->reminderType,
                ]);

                return;
            }

            // Send the reminder
            $this->sendReminderEmail($invoice);

            // Update invoice metadata
            $this->updateInvoiceReminderHistory($invoice);

            Log::info('Invoice reminder sent successfully', [
                'invoice_id' => $this->invoiceId,
                'invoice_number' => $invoice->invoice_number,
                'customer_email' => $invoice->customer->email,
                'reminder_type' => $this->reminderType,
            ]);

            // Send success notification to user
            if ($this->userId) {
                $this->sendSuccessNotification($invoice);
            }

        } catch (\Exception $e) {
            Log::error('Failed to send invoice reminder', [
                'invoice_id' => $this->invoiceId,
                'reminder_type' => $this->reminderType,
                'user_id' => $this->userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Send failure notification
            if ($this->userId) {
                $this->sendFailureNotification($e->getMessage());
            }

            throw $e;
        }
    }

    protected function shouldSendReminder(Invoice $invoice): bool
    {
        // Don't send reminders for paid or cancelled invoices
        if (in_array($invoice->status, ['paid', 'cancelled'])) {
            return false;
        }

        // Don't send reminders if customer has no email
        if (empty($invoice->customer->email)) {
            return false;
        }

        return true;
    }

    protected function sendReminderEmail(Invoice $invoice): void
    {
        $reminderData = $this->prepareReminderData($invoice);

        // TODO: Implement actual email sending
        // For now, we'll simulate email sending
        Log::info('Simulating email send to customer', [
            'invoice_id' => $invoice->id,
            'customer_email' => $invoice->customer->email,
            'reminder_type' => $this->reminderType,
            'subject' => $reminderData['subject'],
        ]);

        // In a real implementation, you would do something like:
        // Mail::to($invoice->customer->email)
        //     ->send(new InvoiceReminderMail($invoice, $reminderData));
    }

    protected function prepareReminderData(Invoice $invoice): array
    {
        return match ($this->reminderType) {
            'due_soon' => [
                'subject' => "Payment Reminder: Invoice {$invoice->invoice_number} Due Soon",
                'heading' => 'Payment Due Soon',
                'message' => "Your invoice {$invoice->invoice_number} is due on {$invoice->due_date->format('F j, Y')}.",
                'urgency' => 'medium',
                'action_text' => 'Pay Now',
            ],
            'overdue' => [
                'subject' => "Overdue Payment: Invoice {$invoice->invoice_number}",
                'heading' => 'Payment Overdue',
                'message' => "Your invoice {$invoice->invoice_number} was due on {$invoice->due_date->format('F j, Y')} and is now overdue.",
                'urgency' => 'high',
                'action_text' => 'Pay Now',
            ],
            'final' => [
                'subject' => "Final Notice: Invoice {$invoice->invoice_number}",
                'heading' => 'Final Payment Notice',
                'message' => "This is a final notice for your overdue invoice {$invoice->invoice_number}. Please contact us immediately to resolve this matter.",
                'urgency' => 'critical',
                'action_text' => 'Contact Us',
            ],
            default => [
                'subject' => "Payment Reminder: Invoice {$invoice->invoice_number}",
                'heading' => 'Payment Reminder',
                'message' => "Please review and pay your invoice {$invoice->invoice_number}.",
                'urgency' => 'normal',
                'action_text' => 'View Invoice',
            ],
        };
    }

    protected function updateInvoiceReminderHistory(Invoice $invoice): void
    {
        $metadata = $invoice->metadata ?? [];
        $reminders = $metadata['reminders'] ?? [];

        $reminders[] = [
            'type' => $this->reminderType,
            'sent_at' => now()->toDateTimeString(),
            'sent_by' => $this->userId,
        ];

        $metadata['reminders'] = $reminders;
        $metadata['last_reminder_sent'] = now()->toDateTimeString();
        $metadata['reminder_count'] = count($reminders);

        $invoice->update(['metadata' => $metadata]);

        // Update status to overdue if past due date
        if ($invoice->due_date < now() && $invoice->status === 'sent') {
            $invoice->update(['status' => 'overdue']);
        }
    }

    protected function sendSuccessNotification(Invoice $invoice): void
    {
        if ($user = User::find($this->userId)) {
            Notification::make()
                ->title('Reminder Sent Successfully')
                ->body("Payment reminder sent to {$invoice->customer->name} for invoice {$invoice->invoice_number}")
                ->icon('heroicon-o-envelope')
                ->color('success')
                ->actions([
                    \Filament\Notifications\Actions\Action::make('view_invoice')
                        ->label('View Invoice')
                        ->url("/admin/invoices/{$invoice->id}")
                        ->button(),
                ])
                ->sendToDatabase($user);
        }
    }

    protected function sendFailureNotification(string $error): void
    {
        if ($user = User::find($this->userId)) {
            Notification::make()
                ->title('Reminder Failed')
                ->body("Failed to send payment reminder for invoice #{$this->invoiceId}: {$error}")
                ->icon('heroicon-o-exclamation-triangle')
                ->color('danger')
                ->actions([
                    \Filament\Notifications\Actions\Action::make('retry')
                        ->label('Try Again')
                        ->url("/admin/invoices/{$this->invoiceId}")
                        ->button(),
                ])
                ->sendToDatabase($user);
        }
    }

    public static function scheduleReminders(Invoice $invoice): void
    {
        if ($invoice->status !== 'sent' || ! $invoice->due_date) {
            return;
        }

        $dueDate = $invoice->due_date;
        $now = now();

        // Schedule "due soon" reminder (7 days before due date)
        $dueSoonDate = $dueDate->copy()->subDays(7);
        if ($dueSoonDate->isFuture()) {
            static::dispatch($invoice->id, 'due_soon')
                ->delay($dueSoonDate);
        }

        // Schedule "due soon" reminder (3 days before due date)
        $dueSoon3Date = $dueDate->copy()->subDays(3);
        if ($dueSoon3Date->isFuture()) {
            static::dispatch($invoice->id, 'due_soon')
                ->delay($dueSoon3Date);
        }

        // Schedule "due soon" reminder (1 day before due date)
        $dueSoon1Date = $dueDate->copy()->subDay();
        if ($dueSoon1Date->isFuture()) {
            static::dispatch($invoice->id, 'due_soon')
                ->delay($dueSoon1Date);
        }

        // Schedule "overdue" reminder (3 days after due date)
        $overdueDate = $dueDate->copy()->addDays(3);
        static::dispatch($invoice->id, 'overdue')
            ->delay($overdueDate);

        // Schedule "final" reminder (14 days after due date)
        $finalDate = $dueDate->copy()->addDays(14);
        static::dispatch($invoice->id, 'final')
            ->delay($finalDate);

        Log::info('Scheduled invoice reminders', [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'due_date' => $dueDate->format('Y-m-d'),
            'scheduled_reminders' => [
                'due_soon_7' => $dueSoonDate->isFuture() ? $dueSoonDate->format('Y-m-d H:i') : 'skipped',
                'due_soon_3' => $dueSoon3Date->isFuture() ? $dueSoon3Date->format('Y-m-d H:i') : 'skipped',
                'due_soon_1' => $dueSoon1Date->isFuture() ? $dueSoon1Date->format('Y-m-d H:i') : 'skipped',
                'overdue' => $overdueDate->format('Y-m-d H:i'),
                'final' => $finalDate->format('Y-m-d H:i'),
            ],
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Invoice reminder job failed permanently', [
            'invoice_id' => $this->invoiceId,
            'reminder_type' => $this->reminderType,
            'user_id' => $this->userId,
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        if ($this->userId) {
            $this->sendFailureNotification($exception->getMessage());
        }
    }

    public function retryUntil(): \DateTime
    {
        return now()->addHours(6);
    }

    public function backoff(): array
    {
        return [60, 300, 900]; // Retry after 1m, 5m, 15m
    }
}

