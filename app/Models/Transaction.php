<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class Transaction extends Model
{
    /** @use HasFactory<\Database\Factories\TransactionFactory> */
    use HasFactory;

    protected $fillable = [
        'type',
        'amount',
        'currency',
        'title',
        'description',
        'transaction_date',
        'category_id',
        'payment_method_id',
        'transactionable_type',
        'transactionable_id',
        'status',
        'processed_at',
        'reference_number',
        'external_reference',
        'metadata',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'transaction_date' => 'date',
            'processed_at' => 'datetime',
            'approved_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Transaction $transaction): void {
            if (empty($transaction->reference_number)) {
                $transaction->reference_number = $transaction->generateReferenceNumber();
            }

        });

    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function transactionable(): MorphTo
    {
        return $this->morphTo();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Scopes
    public function scopeIncome($query)
    {
        return $query->where('type', 'income');
    }

    public function scopeExpense($query)
    {
        return $query->where('type', 'expense');
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->byStatus('pending');
    }

    public function scopeCompleted($query)
    {
        return $query->byStatus('completed');
    }

    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeByPaymentMethod($query, $paymentMethodId)
    {
        return $query->where('payment_method_id', $paymentMethodId);
    }

    // Methods
    public function generateReferenceNumber(): string
    {
        $prefix = strtoupper($this->type[0] ?? 'T');
        $timestamp = now()->format('YmdHis');
        $random = Str::random(4);

        return "{$prefix}{$timestamp}{$random}";
    }

    public function approve(User $user): void
    {
        $this->update([
            'status' => 'completed',
            'approved_by' => $user->id,
            'approved_at' => now(),
            'processed_at' => now(),
        ]);
    }

    public function cancel(): void
    {
        $this->update([
            'status' => 'cancelled',
        ]);
    }

    public function refund(): void
    {
        $this->update([
            'status' => 'refunded',
        ]);
    }

    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 2).' '.$this->currency;
    }

    public function getIsIncomeAttribute(): bool
    {
        return $this->type === 'income';
    }

    public function getIsExpenseAttribute(): bool
    {
        return $this->type === 'expense';
    }
}
