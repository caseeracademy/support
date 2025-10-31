<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class RecurringTransaction extends Model
{
    /** @use HasFactory<\Database\Factories\RecurringTransactionFactory> */
    use HasFactory;

    protected $fillable = [
        'type',
        'amount',
        'currency',
        'title',
        'description',
        'category_id',
        'payment_method_id',
        'recurrable_type',
        'recurrable_id',
        'frequency',
        'interval',
        'start_date',
        'end_date',
        'max_occurrences',
        'is_active',
        'next_due_date',
        'occurrences_created',
        'last_processed_at',
        'metadata',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
            'next_due_date' => 'date',
            'last_processed_at' => 'datetime',
            'is_active' => 'boolean',
            'metadata' => 'array',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (RecurringTransaction $recurringTransaction): void {
            if (empty($recurringTransaction->next_due_date)) {
                $recurringTransaction->next_due_date = $recurringTransaction->start_date;
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

    public function recurrable(): MorphTo
    {
        return $this->morphTo();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDue($query, ?Carbon $date = null)
    {
        $date = $date ?? now();

        return $query->where('next_due_date', '<=', $date->format('Y-m-d'));
    }

    public function scopeByFrequency($query, string $frequency)
    {
        return $query->where('frequency', $frequency);
    }

    public function scopeIncome($query)
    {
        return $query->where('type', 'income');
    }

    public function scopeExpense($query)
    {
        return $query->where('type', 'expense');
    }

    // Methods
    public function createTransaction(): Transaction
    {
        $transaction = Transaction::create([
            'type' => $this->type,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'title' => $this->title.' (Recurring)',
            'description' => $this->description,
            'transaction_date' => $this->next_due_date,
            'category_id' => $this->category_id,
            'payment_method_id' => $this->payment_method_id,
            'transactionable_type' => $this->recurrable_type,
            'transactionable_id' => $this->recurrable_id,
            'status' => 'pending',
            'metadata' => array_merge($this->metadata ?? [], [
                'recurring_transaction_id' => $this->id,
                'occurrence_number' => $this->occurrences_created + 1,
            ]),
            'created_by' => $this->created_by,
        ]);

        $this->increment('occurrences_created');
        $this->updateNextDueDate();
        $this->update(['last_processed_at' => now()]);

        // Check if we should deactivate
        if ($this->shouldStop()) {
            $this->update(['is_active' => false]);
        }

        return $transaction;
    }

    public function updateNextDueDate(): void
    {
        $nextDate = Carbon::parse($this->next_due_date);

        switch ($this->frequency) {
            case 'daily':
                $nextDate->addDays($this->interval);
                break;
            case 'weekly':
                $nextDate->addWeeks($this->interval);
                break;
            case 'monthly':
                $nextDate->addMonths($this->interval);
                break;
            case 'quarterly':
                $nextDate->addMonths(3 * $this->interval);
                break;
            case 'yearly':
                $nextDate->addYears($this->interval);
                break;
        }

        $this->update(['next_due_date' => $nextDate]);
    }

    public function shouldStop(): bool
    {
        // Check max occurrences
        if ($this->max_occurrences && $this->occurrences_created >= $this->max_occurrences) {
            return true;
        }

        // Check end date
        if ($this->end_date && now()->gt($this->end_date)) {
            return true;
        }

        return false;
    }

    public function pause(): void
    {
        $this->update(['is_active' => false]);
    }

    public function resume(): void
    {
        $this->update(['is_active' => true]);
    }

    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 2).' '.$this->currency;
    }

    public function getFrequencyDisplayAttribute(): string
    {
        $display = ucfirst($this->frequency);

        if ($this->interval > 1) {
            $display = "Every {$this->interval} {$display}";
        }

        return $display;
    }
}
