<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetCategory extends Model
{
    protected $fillable = [
        'budget_id',
        'category_id',
        'allocated_amount',
        'spent_amount',
        'alert_at_80_percent',
        'alert_at_100_percent',
        'last_alert_sent',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'allocated_amount' => 'decimal:2',
            'spent_amount' => 'decimal:2',
            'alert_at_80_percent' => 'boolean',
            'alert_at_100_percent' => 'boolean',
            'last_alert_sent' => 'datetime',
            'metadata' => 'array',
        ];
    }

    // Relationships
    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    // Computed Properties
    public function getRemainingAmountAttribute(): float
    {
        return max(0, $this->allocated_amount - $this->spent_amount);
    }

    public function getPercentageUsedAttribute(): float
    {
        if ($this->allocated_amount <= 0) {
            return 0;
        }

        return ($this->spent_amount / $this->allocated_amount) * 100;
    }

    public function getIsOverBudgetAttribute(): bool
    {
        return $this->spent_amount > $this->allocated_amount;
    }

    public function getVarianceAttribute(): float
    {
        return $this->spent_amount - $this->allocated_amount;
    }

    public function getVariancePercentageAttribute(): float
    {
        if ($this->allocated_amount <= 0) {
            return 0;
        }

        return ($this->variance / $this->allocated_amount) * 100;
    }

    public function getFormattedAllocatedAmountAttribute(): string
    {
        return '$'.number_format($this->allocated_amount, 2);
    }

    public function getFormattedSpentAmountAttribute(): string
    {
        return '$'.number_format($this->spent_amount, 2);
    }

    public function getFormattedRemainingAmountAttribute(): string
    {
        return '$'.number_format($this->remaining_amount, 2);
    }

    public function getFormattedVarianceAttribute(): string
    {
        $variance = $this->variance;
        $sign = $variance >= 0 ? '+' : '';

        return $sign.'$'.number_format(abs($variance), 2);
    }

    public function getStatusColorAttribute(): string
    {
        $percentage = $this->percentage_used;

        if ($percentage >= 100) {
            return 'danger';
        } elseif ($percentage >= 80) {
            return 'warning';
        } elseif ($percentage >= 50) {
            return 'info';
        } else {
            return 'success';
        }
    }

    public function getAlertStatusAttribute(): string
    {
        $percentage = $this->percentage_used;

        if ($percentage >= 100) {
            return 'over_budget';
        } elseif ($percentage >= 80) {
            return 'approaching_limit';
        } else {
            return 'on_track';
        }
    }

    // Methods
    public function updateSpentAmount(): void
    {
        $spent = Transaction::where('category_id', $this->category_id)
            ->where('type', 'expense')
            ->where('status', 'completed')
            ->whereBetween('transaction_date', [$this->budget->start_date, $this->budget->end_date])
            ->sum('amount');

        $this->update(['spent_amount' => $spent]);
    }

    public function needsAlert(): bool
    {
        $percentage = $this->percentage_used;

        // Check if we need 80% alert
        if ($percentage >= 80 && $this->alert_at_80_percent && ! $this->hasRecentAlert()) {
            return true;
        }

        // Check if we need 100% alert
        if ($percentage >= 100 && $this->alert_at_100_percent && ! $this->hasRecentAlert()) {
            return true;
        }

        return false;
    }

    public function hasRecentAlert(): bool
    {
        if (! $this->last_alert_sent) {
            return false;
        }

        // Don't send alerts more than once per day
        return $this->last_alert_sent->diffInHours(now()) < 24;
    }

    public function markAlertSent(): void
    {
        $this->update(['last_alert_sent' => now()]);
    }

    public function getDailyBurnRate(): float
    {
        $daysElapsed = $this->budget->start_date->diffInDays(now()) + 1;

        if ($daysElapsed <= 0) {
            return 0;
        }

        return $this->spent_amount / $daysElapsed;
    }

    public function getProjectedSpend(): float
    {
        $dailyBurnRate = $this->getDailyBurnRate();
        $totalDays = $this->budget->duration_in_days;

        return $dailyBurnRate * $totalDays;
    }

    public function isOnTrack(): bool
    {
        $projectedSpend = $this->getProjectedSpend();

        return $projectedSpend <= $this->allocated_amount;
    }
}
