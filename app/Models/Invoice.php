<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    /** @use HasFactory<\Database\Factories\InvoiceFactory> */
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'customer_id',
        'ticket_id',
        'title',
        'description',
        'invoice_date',
        'due_date',
        'subtotal',
        'tax_rate',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'paid_amount',
        'currency',
        'status',
        'items',
        'sent_at',
        'paid_at',
        'reference_number',
        'notes',
        'pdf_path',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'items' => 'array',
            'sent_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Invoice $invoice): void {
            if (empty($invoice->invoice_number)) {
                $invoice->invoice_number = $invoice->generateInvoiceNumber();
            }
        });

        static::updating(function (Invoice $invoice): void {
            // Auto-update status based on payments
            if ($invoice->isDirty('paid_amount')) {
                $invoice->updateStatus();
            }

            // Schedule reminders when invoice is sent
            if ($invoice->isDirty('status') && $invoice->status === 'sent') {
                $invoice->scheduleAutomaticReminders();
            }
        });
    }

    // Relationships
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue')
            ->orWhere(function ($query): void {
                $query->whereIn('status', ['sent'])
                    ->where('due_date', '<', now());
            });
    }

    public function scopeUnpaid($query)
    {
        return $query->whereIn('status', ['sent', 'overdue']);
    }

    // Methods
    public function generateInvoiceNumber(): string
    {
        $prefix = 'INV';
        $year = now()->format('Y');
        $month = now()->format('m');

        // Get the latest invoice number for this month
        $latestInvoice = static::where('invoice_number', 'like', "{$prefix}-{$year}{$month}-%")
            ->orderBy('invoice_number', 'desc')
            ->first();

        $sequence = 1;
        if ($latestInvoice) {
            $lastNumber = explode('-', $latestInvoice->invoice_number)[1] ?? '';
            $lastSequence = (int) substr($lastNumber, -4);
            $sequence = $lastSequence + 1;
        }

        return sprintf('%s-%s%s-%04d', $prefix, $year, $month, $sequence);
    }

    public function updateStatus(): void
    {
        if ($this->paid_amount >= $this->total_amount) {
            $this->status = 'paid';
            if (! $this->paid_at) {
                $this->paid_at = now();
            }
        } elseif ($this->paid_amount > 0) {
            $this->status = 'sent'; // Partial payment
        } elseif ($this->due_date < now() && $this->status === 'sent') {
            $this->status = 'overdue';
        }
    }

    public function markAsSent(): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    public function markAsPaid(?float $amount = null): void
    {
        $paidAmount = $amount ?? $this->total_amount;

        $this->update([
            'paid_amount' => $paidAmount,
            'status' => 'paid',
            'paid_at' => now(),
        ]);
    }

    public function addPayment(float $amount): void
    {
        $newPaidAmount = $this->paid_amount + $amount;

        $this->update([
            'paid_amount' => min($newPaidAmount, $this->total_amount),
        ]);
    }

    public function calculateAmounts(): void
    {
        $subtotal = 0;

        foreach ($this->items as $item) {
            $itemTotal = ($item['quantity'] ?? 1) * ($item['unit_price'] ?? 0);
            $subtotal += $itemTotal;
        }

        $this->subtotal = $subtotal;
        $this->tax_amount = $subtotal * ($this->tax_rate / 100);
        $this->total_amount = $subtotal + $this->tax_amount - $this->discount_amount;
    }

    // Computed attributes
    public function getRemainingAmountAttribute(): float
    {
        return max(0, $this->total_amount - $this->paid_amount);
    }

    public function getPaymentProgressAttribute(): float
    {
        if ($this->total_amount <= 0) {
            return 0;
        }

        return min(100, ($this->paid_amount / $this->total_amount) * 100);
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->due_date < now() && ! in_array($this->status, ['paid', 'cancelled']);
    }

    public function getDaysOverdueAttribute(): int
    {
        if (! $this->is_overdue) {
            return 0;
        }

        return $this->due_date->diffInDays(now());
    }

    public function getFormattedTotalAmountAttribute(): string
    {
        return '$'.number_format($this->total_amount, 2).' '.$this->currency;
    }

    public function getFormattedRemainingAmountAttribute(): string
    {
        return '$'.number_format($this->remaining_amount, 2).' '.$this->currency;
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'gray',
            'sent' => 'info',
            'paid' => 'success',
            'overdue' => 'danger',
            'cancelled' => 'warning',
            default => 'gray',
        };
    }

    // Automation Methods
    public function sendReminder(string $type = 'standard', ?int $userId = null): void
    {
        \App\Jobs\SendInvoiceReminder::dispatch($this->id, $type, $userId);
    }

    public function scheduleAutomaticReminders(): void
    {
        if ($this->status === 'sent' && $this->due_date) {
            \App\Jobs\SendInvoiceReminder::scheduleReminders($this);
        }
    }

    public function autoUpdateStatusBasedOnDate(): void
    {
        if ($this->due_date && $this->due_date < now() && $this->status === 'sent') {
            $this->update(['status' => 'overdue']);
        }
    }

    public function autoMatchPayments(): bool
    {
        if (! $this->customer) {
            return false;
        }

        // Find transactions that might be payments for this invoice
        $potentialPayments = $this->customer->transactions()
            ->where('type', 'income')
            ->where('status', 'completed')
            ->where('amount', '>=', $this->remaining_amount)
            ->whereDate('transaction_date', '>=', $this->invoice_date)
            ->whereNull('transactionable_id') // Not already linked
            ->get();

        foreach ($potentialPayments as $payment) {
            // Check if payment amount matches remaining amount or total amount
            if ($payment->amount == $this->remaining_amount || $payment->amount == $this->total_amount) {
                // Link payment to invoice
                $payment->update([
                    'transactionable_type' => static::class,
                    'transactionable_id' => $this->id,
                    'metadata' => array_merge($payment->metadata ?? [], [
                        'auto_matched_invoice' => $this->invoice_number,
                        'matched_at' => now()->toDateTimeString(),
                    ]),
                ]);

                // Update invoice payment
                $this->addPayment($payment->amount);

                return true;
            }
        }

        return false;
    }

    public function generatePdf(string $template = 'standard'): string
    {
        $pdfService = app(\App\Services\InvoicePdfService::class);

        return $pdfService->generatePdf($this, $template);
    }

    public function downloadPdf(string $template = 'standard'): \Symfony\Component\HttpFoundation\Response
    {
        $pdfService = app(\App\Services\InvoicePdfService::class);

        return $pdfService->downloadPdf($this, $template);
    }

    public function canSendReminder(): bool
    {
        return in_array($this->status, ['sent', 'overdue']) &&
               $this->remaining_amount > 0 &&
               $this->customer?->email;
    }

    public function getLastReminderSent(): ?Carbon
    {
        $metadata = $this->metadata ?? [];
        $lastReminder = $metadata['last_reminder_sent'] ?? null;

        return $lastReminder ? Carbon::parse($lastReminder) : null;
    }

    public function getReminderCount(): int
    {
        $metadata = $this->metadata ?? [];

        return $metadata['reminder_count'] ?? 0;
    }

    public function shouldReceiveReminder(string $type = 'standard'): bool
    {
        if (! $this->canSendReminder()) {
            return false;
        }

        $lastReminder = $this->getLastReminderSent();
        $reminderCount = $this->getReminderCount();

        return match ($type) {
            'due_soon' => $this->due_date && $this->due_date->diffInDays(now()) <= 7,
            'overdue' => $this->is_overdue && (! $lastReminder || $lastReminder->diffInDays(now()) >= 3),
            'final' => $this->is_overdue && $this->days_overdue >= 14 && $reminderCount < 3,
            default => ! $lastReminder || $lastReminder->diffInDays(now()) >= 7,
        };
    }

    public static function autoGenerateFromTicket(int $ticketId, array $data = [], ?int $userId = null): void
    {
        \App\Jobs\GenerateInvoiceFromTicket::dispatch($ticketId, $userId, $data);
    }

    public static function processAutomaticActions(): void
    {
        // Update overdue statuses
        static::where('status', 'sent')
            ->where('due_date', '<', now())
            ->update(['status' => 'overdue']);

        // Auto-match payments for recent invoices
        static::whereIn('status', ['sent', 'overdue'])
            ->where('remaining_amount', '>', 0)
            ->where('invoice_date', '>=', now()->subMonths(3))
            ->chunk(50, function ($invoices) {
                foreach ($invoices as $invoice) {
                    $invoice->autoMatchPayments();
                }
            });
    }

}
