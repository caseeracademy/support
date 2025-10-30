<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Models\Ticket;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateInvoiceFromTicket implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;

    public int $maxExceptions = 3;

    public function __construct(
        protected int $ticketId,
        protected ?int $userId = null,
        protected array $invoiceData = []
    ) {
        $this->onQueue('invoices');
    }

    public function handle(): void
    {
        try {
            $ticket = Ticket::with(['customer', 'invoices'])->find($this->ticketId);

            if (! $ticket) {
                throw new \Exception("Ticket not found: {$this->ticketId}");
            }

            // Check if ticket already has an invoice
            if ($ticket->invoices()->exists()) {
                Log::info('Ticket already has invoice, skipping auto-generation', [
                    'ticket_id' => $this->ticketId,
                ]);

                return;
            }

            // Determine invoice details based on ticket
            $invoiceData = $this->prepareInvoiceData($ticket);

            // Create the invoice
            $invoice = Invoice::create($invoiceData);

            Log::info('Auto-generated invoice from ticket', [
                'ticket_id' => $this->ticketId,
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
            ]);

            // Send notification to user who requested it
            if ($this->userId) {
                $this->sendNotification($ticket, $invoice);
            }

        } catch (\Exception $e) {
            Log::error('Failed to generate invoice from ticket', [
                'ticket_id' => $this->ticketId,
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

    protected function prepareInvoiceData(Ticket $ticket): array
    {
        // Merge provided data with calculated values
        $calculatedData = $this->calculateInvoiceData($ticket);

        return array_merge($calculatedData, $this->invoiceData);
    }

    protected function calculateInvoiceData(Ticket $ticket): array
    {
        // Determine invoice title and description
        $title = $this->invoiceData['title'] ?? "Support Services - Ticket #{$ticket->id}";
        $description = $this->invoiceData['description'] ?? $ticket->subject;

        // Calculate amounts
        $subtotal = $ticket->total_amount ?? 0;
        $taxRate = $this->invoiceData['tax_rate'] ?? 0;
        $taxAmount = $subtotal * ($taxRate / 100);
        $discountAmount = $this->invoiceData['discount_amount'] ?? 0;
        $totalAmount = $subtotal + $taxAmount - $discountAmount;

        // Prepare invoice items from ticket details
        $items = $this->prepareInvoiceItems($ticket);

        // Set due date (default: 30 days from now)
        $dueDate = $this->invoiceData['due_date'] ?? now()->addDays(30);

        return [
            'customer_id' => $ticket->customer_id,
            'ticket_id' => $ticket->id,
            'title' => $title,
            'description' => $description,
            'invoice_date' => now(),
            'due_date' => $dueDate,
            'subtotal' => $subtotal,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'discount_amount' => $discountAmount,
            'total_amount' => $totalAmount,
            'paid_amount' => 0,
            'currency' => $ticket->currency ?? 'USD',
            'status' => 'draft',
            'items' => $items,
            'created_by' => $this->userId,
            'notes' => $this->generateInvoiceNotes($ticket),
        ];
    }

    protected function prepareInvoiceItems(Ticket $ticket): array
    {
        $items = [];

        // Main service item
        $items[] = [
            'description' => $ticket->course_name ? "Support for {$ticket->course_name}" : 'Technical Support',
            'quantity' => 1,
            'unit_price' => $ticket->total_amount ?? 0,
            'total' => $ticket->total_amount ?? 0,
        ];

        // Add additional items if specified in invoice data
        if (! empty($this->invoiceData['additional_items'])) {
            $items = array_merge($items, $this->invoiceData['additional_items']);
        }

        return $items;
    }

    protected function generateInvoiceNotes(Ticket $ticket): string
    {
        $notes = [];

        $notes[] = "Generated automatically from Support Ticket #{$ticket->id}";
        $notes[] = "Ticket Subject: {$ticket->subject}";

        if ($ticket->course_name) {
            $notes[] = "Course: {$ticket->course_name}";
        }

        if ($ticket->priority !== 'medium') {
            $notes[] = 'Priority: '.ucfirst($ticket->priority);
        }

        // Add custom notes if provided
        if (! empty($this->invoiceData['custom_notes'])) {
            $notes[] = $this->invoiceData['custom_notes'];
        }

        return implode("\n", $notes);
    }

    protected function sendNotification(Ticket $ticket, Invoice $invoice): void
    {
        if ($user = User::find($this->userId)) {
            Notification::make()
                ->title('Invoice Generated Successfully')
                ->body("Invoice {$invoice->invoice_number} has been created for Ticket #{$ticket->id}")
                ->icon('heroicon-o-document')
                ->color('success')
                ->actions([
                    \Filament\Notifications\Actions\Action::make('view_invoice')
                        ->label('View Invoice')
                        ->url("/admin/invoices/{$invoice->id}")
                        ->button(),

                    \Filament\Notifications\Actions\Action::make('view_ticket')
                        ->label('View Ticket')
                        ->url("/admin/tickets/{$ticket->id}")
                        ->button()
                        ->color('gray'),
                ])
                ->sendToDatabase($user);
        }
    }

    protected function sendFailureNotification(string $error): void
    {
        if ($user = User::find($this->userId)) {
            Notification::make()
                ->title('Invoice Generation Failed')
                ->body("Failed to generate invoice for Ticket #{$this->ticketId}: {$error}")
                ->icon('heroicon-o-exclamation-triangle')
                ->color('danger')
                ->actions([
                    \Filament\Notifications\Actions\Action::make('retry')
                        ->label('Try Again')
                        ->url("/admin/tickets/{$this->ticketId}")
                        ->button(),
                ])
                ->sendToDatabase($user);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Invoice generation job failed permanently', [
            'ticket_id' => $this->ticketId,
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
        return now()->addHours(2);
    }

    public function backoff(): array
    {
        return [30, 60, 120]; // Retry after 30s, 1m, 2m
    }
}




