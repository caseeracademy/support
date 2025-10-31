<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Ticket extends Model
{
    /** @use HasFactory<\Database\Factories\TicketFactory> */
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'course_name',
        'subject',
        'description',
        'status',
        'priority',
        'assigned_to',
        'payment_status',
        'total_amount',
        'paid_amount',
        'currency',
        'order_reference',
        'payment_reference',
        'payment_due_date',
        'paid_at',
        'payment_approved_at',
        'payment_approved_by',
        'payment_metadata',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'payment_due_date' => 'datetime',
            'paid_at' => 'datetime',
            'payment_approved_at' => 'datetime',
            'payment_metadata' => 'array',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(TicketNote::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class);
    }

    public function transactions(): MorphMany
    {
        return $this->morphMany(Transaction::class, 'transactionable');
    }

    public function recurringTransactions(): MorphMany
    {
        return $this->morphMany(RecurringTransaction::class, 'recurrable');
    }

    public function paymentApprovedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payment_approved_by');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    // Payment-related scopes
    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    public function scopePending($query)
    {
        return $query->where('payment_status', 'pending');
    }

    public function scopeOverdue($query)
    {
        return $query->where('payment_status', 'pending')
            ->whereNotNull('payment_due_date')
            ->where('payment_due_date', '<', now());
    }

    public function scopePartiallyPaid($query)
    {
        return $query->where('payment_status', 'partial');
    }

    // Payment methods
    public function markAsPaid(User $user, ?float $amount = null): void
    {
        $paidAmount = $amount ?? $this->total_amount ?? 0;

        $this->update([
            'payment_status' => $paidAmount >= ($this->total_amount ?? 0) ? 'paid' : 'partial',
            'paid_amount' => $paidAmount,
            'paid_at' => now(),
            'payment_approved_by' => $user->id,
            'payment_approved_at' => now(),
        ]);
    }

    public function markAsRefunded(): void
    {
        $this->update([
            'payment_status' => 'refunded',
            'paid_amount' => 0,
        ]);
    }

    public function markAsCancelled(): void
    {
        $this->update([
            'payment_status' => 'cancelled',
        ]);
    }

    public function addPayment(float $amount, User $user, ?int $paymentMethodId = null): void
    {
        $newPaidAmount = ($this->paid_amount ?? 0) + $amount;

        $this->update([
            'paid_amount' => $newPaidAmount,
            'payment_status' => $newPaidAmount >= ($this->total_amount ?? 0) ? 'paid' : 'partial',
            'payment_approved_by' => $user->id,
            'payment_approved_at' => now(),
        ]);

        if ($this->payment_status === 'paid' && ! $this->paid_at) {
            $this->update(['paid_at' => now()]);
        }

        // Create a transaction record for this payment
        $this->createPaymentTransaction($amount, $user, $paymentMethodId);
    }

    public function createPaymentTransaction(float $amount, User $user, ?int $paymentMethodId = null): Transaction
    {
        // Get the course sales category (or first income category, or create one)
        $category = \App\Models\Category::where('type', 'income')
            ->where('slug', 'course-sales')
            ->first()
            ?? \App\Models\Category::where('type', 'income')->first();

        // If no income category exists, create a default one
        if (! $category) {
            $category = \App\Models\Category::create([
                'name' => 'Course Sales',
                'slug' => 'course-sales',
                'type' => 'income',
                'color' => '#10B981',
                'is_active' => true,
            ]);
        }

        // Use provided payment method or get default
        $paymentMethod = $paymentMethodId
            ? \App\Models\PaymentMethod::find($paymentMethodId)
            : \App\Models\PaymentMethod::where('is_active', true)->first();

        if (! $paymentMethod) {
            throw new \Exception('No active payment method found. Please create at least one payment method/account in the Accounts section.');
        }

        return Transaction::create([
            'type' => 'income',
            'amount' => $amount,
            'currency' => $this->currency ?? 'USD',
            'title' => "Payment for Ticket #{$this->id} - {$this->course_name}",
            'description' => "Payment received for {$this->subject} (Customer: {$this->customer->name})",
            'transaction_date' => now(),
            'category_id' => $category->id,
            'payment_method_id' => $paymentMethod->id,
            'transactionable_type' => Ticket::class,
            'transactionable_id' => $this->id,
            'status' => 'completed',
            'processed_at' => now(),
            'reference_number' => null, // Will be auto-generated
            'external_reference' => $this->order_reference,
            'metadata' => [
                'ticket_id' => $this->id,
                'customer_id' => $this->customer_id,
                'order_reference' => $this->order_reference,
            ],
            'created_by' => $user->id,
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);
    }

    // Computed attributes
    public function getRemainingAmountAttribute(): float
    {
        return max(0, ($this->total_amount ?? 0) - ($this->paid_amount ?? 0));
    }

    public function getPaymentProgressAttribute(): float
    {
        if (! $this->total_amount || $this->total_amount <= 0) {
            return 0;
        }

        return min(100, ($this->paid_amount / $this->total_amount) * 100);
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->payment_status === 'pending'
            && $this->payment_due_date
            && now()->gt($this->payment_due_date);
    }

    public function getFormattedTotalAmountAttribute(): string
    {
        if (! $this->total_amount) {
            return 'N/A';
        }

        return number_format($this->total_amount, 2).' '.$this->currency;
    }

    public function getFormattedPaidAmountAttribute(): string
    {
        return number_format($this->paid_amount ?? 0, 2).' '.$this->currency;
    }

    // Enhanced Financial Integration Methods
    public function generateInvoice(array $data = []): Invoice
    {
        $invoiceData = array_merge([
            'customer_id' => $this->customer_id,
            'ticket_id' => $this->id,
            'title' => "Support Services - Ticket #{$this->id}",
            'description' => $this->subject,
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
            'subtotal' => $this->total_amount ?? 0,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => $this->total_amount ?? 0,
            'paid_amount' => 0,
            'currency' => $this->currency ?? 'USD',
            'status' => 'draft',
            'items' => [
                [
                    'description' => $this->course_name ? "Support for {$this->course_name}" : 'Technical Support',
                    'quantity' => 1,
                    'unit_price' => $this->total_amount ?? 0,
                    'total' => $this->total_amount ?? 0,
                ],
            ],
        ], $data);

        return $this->invoices()->create($invoiceData);
    }

    public function autoGenerateInvoice(): void
    {
        // Only auto-generate if ticket is resolved and has amount but no invoice
        if ($this->status === 'resolved' &&
            $this->total_amount &&
            $this->total_amount > 0 &&
            ! $this->invoices()->exists()) {

            Invoice::autoGenerateFromTicket($this->id);
        }
    }

    public function createTransactionFromPayment(float $amount, array $data = []): Transaction
    {
        $transactionData = array_merge([
            'type' => 'income',
            'amount' => $amount,
            'currency' => $this->currency ?? 'USD',
            'title' => "Payment for Ticket #{$this->id}",
            'description' => $this->subject,
            'transaction_date' => now(),
            'category_id' => Category::where('type', 'income')->first()?->id,
            'payment_method_id' => PaymentMethod::first()?->id,
            'transactionable_type' => static::class,
            'transactionable_id' => $this->id,
            'status' => 'completed',
            'processed_at' => now(),
            'created_by' => \Illuminate\Support\Facades\Auth::id(),
        ], $data);

        return Transaction::create($transactionData);
    }

    public function getInvoiceStatus(): string
    {
        $invoice = $this->invoices()->latest()->first();

        if (! $invoice) {
            return 'no_invoice';
        }

        return $invoice->status;
    }

    public function hasOutstandingInvoice(): bool
    {
        return $this->invoices()->whereIn('status', ['sent', 'overdue'])->exists();
    }

    public function getInvoiceSummary(): array
    {
        $invoices = $this->invoices;

        return [
            'count' => $invoices->count(),
            'total_invoiced' => $invoices->sum('total_amount'),
            'total_paid' => $invoices->sum('paid_amount'),
            'outstanding' => $invoices->whereIn('status', ['sent', 'overdue'])->sum('remaining_amount'),
            'overdue_count' => $invoices->where('status', 'overdue')->count(),
            'formatted_total' => '$'.number_format($invoices->sum('total_amount'), 2),
            'formatted_outstanding' => '$'.number_format($invoices->whereIn('status', ['sent', 'overdue'])->sum('remaining_amount'), 2),
        ];
    }

    public function autoCreateTransactionOnPayment(): void
    {
        // When a payment is recorded, auto-create a transaction
        if ($this->payment_status === 'paid' && $this->paid_amount > 0) {
            // Check if transaction already exists
            $existingTransaction = $this->transactions()
                ->where('type', 'income')
                ->where('amount', $this->paid_amount)
                ->exists();

            if (! $existingTransaction) {
                $this->createTransactionFromPayment($this->paid_amount);
            }
        }
    }
}
