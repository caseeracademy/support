<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Customer extends Model
{
    /** @use HasFactory<\Database\Factories\CustomerFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'phone_number',
        'email',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function transactions(): MorphMany
    {
        return $this->morphMany(Transaction::class, 'transactionable');
    }

    public function recurringTransactions(): MorphMany
    {
        return $this->morphMany(RecurringTransaction::class, 'recurrable');
    }

    // Financial methods
    public function getTotalIncome(): float
    {
        return (float) $this->transactions()
            ->where('type', 'income')
            ->where('status', 'completed')
            ->sum('amount');
    }

    public function getTotalExpenses(): float
    {
        return (float) $this->transactions()
            ->where('type', 'expense')
            ->where('status', 'completed')
            ->sum('amount');
    }

    public function getNetBalance(): float
    {
        return $this->getTotalIncome() - $this->getTotalExpenses();
    }

    public function getPendingPayments(): float
    {
        return (float) $this->transactions()
            ->where('type', 'income')
            ->where('status', 'pending')
            ->sum('amount');
    }

    public function getOverdueTickets(): HasMany
    {
        return $this->tickets()
            ->where('payment_status', 'pending')
            ->whereNotNull('payment_due_date')
            ->where('payment_due_date', '<', now());
    }

    public function scopeWithOutstandingBalance($query)
    {
        return $query->whereHas('tickets', function ($query): void {
            $query->where('payment_status', 'pending')
                ->whereColumn('paid_amount', '<', 'total_amount');
        });
    }

    // Enhanced Financial Integration Methods
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function getFinancialProfile(): array
    {
        $tickets = $this->tickets()->with('invoices')->get();
        $invoices = collect();

        foreach ($tickets as $ticket) {
            if ($ticket->invoices) {
                $invoices = $invoices->merge($ticket->invoices);
            }
        }

        return [
            'total_income' => $this->getTotalIncome(),
            'total_expenses' => $this->getTotalExpenses(),
            'net_balance' => $this->getNetBalance(),
            'pending_payments' => $this->getPendingPayments(),
            'total_invoiced' => $invoices->sum('total_amount'),
            'total_paid' => $invoices->sum('paid_amount'),
            'outstanding_invoices' => $invoices->whereIn('status', ['sent', 'overdue'])->sum('remaining_amount'),
            'overdue_invoices' => $invoices->where('status', 'overdue')->count(),
            'formatted_total_income' => '$'.number_format($this->getTotalIncome(), 2),
            'formatted_net_balance' => '$'.number_format($this->getNetBalance(), 2),
            'formatted_outstanding' => '$'.number_format($invoices->whereIn('status', ['sent', 'overdue'])->sum('remaining_amount'), 2),
        ];
    }

    public function getPaymentHistory(int $limit = 10): array
    {
        $transactions = $this->transactions()
            ->where('status', 'completed')
            ->orderBy('transaction_date', 'desc')
            ->limit($limit)
            ->with(['category', 'paymentMethod'])
            ->get();

        return $transactions->map(function ($transaction) {
            return [
                'date' => $transaction->transaction_date->format('M j, Y'),
                'type' => $transaction->type,
                'amount' => $transaction->amount,
                'formatted_amount' => $transaction->formatted_amount,
                'description' => $transaction->title,
                'reference' => $transaction->reference_number,
            ];
        })->toArray();
    }

    public function getLifetimeValue(): float
    {
        return $this->getTotalIncome();
    }

    public function getAverageTransactionValue(): float
    {
        $transactions = $this->transactions()->where('status', 'completed')->get();

        if ($transactions->isEmpty()) {
            return 0;
        }

        return $transactions->avg('amount');
    }

    public function getPaymentReliabilityScore(): int
    {
        $invoices = collect();
        foreach ($this->tickets as $ticket) {
            if ($ticket->invoices) {
                $invoices = $invoices->merge($ticket->invoices);
            }
        }

        if ($invoices->isEmpty()) {
            return 100; // No invoices = no issues
        }

        $totalInvoices = $invoices->count();
        $paidOnTime = $invoices->filter(function ($invoice) {
            return $invoice->status === 'paid' &&
                   $invoice->paid_at &&
                   $invoice->paid_at->lte($invoice->due_date);
        })->count();

        return (int) (($paidOnTime / $totalInvoices) * 100);
    }

    public function canReceiveCredit(): bool
    {
        $paymentScore = $this->getPaymentReliabilityScore();
        $overdueInvoices = $this->invoices()->where('status', 'overdue')->count();

        return $paymentScore >= 80 && $overdueInvoices === 0;
    }

    public function getOutstandingBalance(): float
    {
        $invoices = collect();
        foreach ($this->tickets as $ticket) {
            if ($ticket->invoices) {
                $invoices = $invoices->merge($ticket->invoices);
            }
        }

        return $invoices->whereIn('status', ['sent', 'overdue'])->sum('remaining_amount');
    }

    public function getFormattedOutstandingBalanceAttribute(): string
    {
        return '$'.number_format($this->getOutstandingBalance(), 2);
    }
}
