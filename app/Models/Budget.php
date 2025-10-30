<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Budget extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'period_type',
        'start_date',
        'end_date',
        'total_amount',
        'currency',
        'status',
        'created_by',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'total_amount' => 'decimal:2',
            'metadata' => 'array',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Budget $budget): void {
            if (empty($budget->end_date)) {
                $budget->end_date = $budget->calculateEndDate();
            }
        });

        static::updated(function (Budget $budget): void {
            if ($budget->isDirty(['start_date', 'period_type'])) {
                $budget->end_date = $budget->calculateEndDate();
                $budget->saveQuietly();
            }
        });
    }

    // Relationships
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'budget_categories')
            ->withPivot(['allocated_amount', 'spent_amount', 'alert_at_80_percent', 'alert_at_100_percent', 'last_alert_sent'])
            ->withTimestamps();
    }

    public function budgetCategories(): HasMany
    {
        return $this->hasMany(BudgetCategory::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeCurrent($query)
    {
        $now = now();

        return $query->where('start_date', '<=', $now)
            ->where('end_date', '>=', $now);
    }

    public function scopeByPeriod($query, string $periodType)
    {
        return $query->where('period_type', $periodType);
    }

    public function scopeExpired($query)
    {
        return $query->where('end_date', '<', now())
            ->where('status', 'active');
    }

    // Computed Properties
    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active';
    }

    public function getIsCurrentAttribute(): bool
    {
        $now = now();

        return $this->start_date <= $now && $this->end_date >= $now;
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->end_date < now();
    }

    public function getDurationInDaysAttribute(): int
    {
        return $this->start_date->diffInDays($this->end_date) + 1;
    }

    public function getDaysRemainingAttribute(): int
    {
        if ($this->is_expired) {
            return 0;
        }

        return now()->diffInDays($this->end_date);
    }

    public function getProgressPercentageAttribute(): float
    {
        if ($this->is_expired) {
            return 100;
        }

        $totalDays = $this->duration_in_days;
        $daysElapsed = $this->start_date->diffInDays(now()) + 1;

        return min(100, ($daysElapsed / $totalDays) * 100);
    }

    public function getTotalAllocatedAttribute(): float
    {
        return $this->budgetCategories->sum('allocated_amount');
    }

    public function getTotalSpentAttribute(): float
    {
        return $this->budgetCategories->sum('spent_amount');
    }

    public function getTotalRemainingAttribute(): float
    {
        return $this->total_allocated - $this->total_spent;
    }

    public function getSpentPercentageAttribute(): float
    {
        if ($this->total_allocated <= 0) {
            return 0;
        }

        return ($this->total_spent / $this->total_allocated) * 100;
    }

    public function getFormattedTotalAmountAttribute(): string
    {
        return '$'.number_format($this->total_amount, 2).' '.$this->currency;
    }

    public function getFormattedTotalSpentAttribute(): string
    {
        return '$'.number_format($this->total_spent, 2).' '.$this->currency;
    }

    public function getFormattedTotalRemainingAttribute(): string
    {
        return '$'.number_format($this->total_remaining, 2).' '.$this->currency;
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'gray',
            'active' => $this->is_expired ? 'danger' : 'success',
            'completed' => 'info',
            'cancelled' => 'warning',
            default => 'gray',
        };
    }

    // Methods
    public function calculateEndDate(): Carbon
    {
        return match ($this->period_type) {
            'monthly' => $this->start_date->copy()->endOfMonth(),
            'quarterly' => $this->start_date->copy()->addMonths(3)->subDay(),
            'yearly' => $this->start_date->copy()->endOfYear(),
            default => $this->start_date->copy()->endOfMonth(),
        };
    }

    public function updateSpentAmounts(): void
    {
        foreach ($this->budgetCategories as $budgetCategory) {
            $spent = Transaction::where('category_id', $budgetCategory->category_id)
                ->where('type', 'expense')
                ->where('status', 'completed')
                ->whereBetween('transaction_date', [$this->start_date, $this->end_date])
                ->sum('amount');

            $budgetCategory->update(['spent_amount' => $spent]);
        }
    }

    public function checkBudgetAlerts(): array
    {
        $alerts = [];

        foreach ($this->budgetCategories as $budgetCategory) {
            $percentage = $budgetCategory->percentage_used;
            $category = $budgetCategory->category;

            // Check 80% threshold
            if ($percentage >= 80 && $budgetCategory->alert_at_80_percent && ! $this->hasRecentAlert($budgetCategory, 80)) {
                $alerts[] = [
                    'type' => 'approaching_limit',
                    'category' => $category->name,
                    'percentage' => $percentage,
                    'allocated' => $budgetCategory->allocated_amount,
                    'spent' => $budgetCategory->spent_amount,
                    'remaining' => $budgetCategory->remaining_amount,
                ];

                $this->markAlertSent($budgetCategory);
            }

            // Check 100% threshold
            if ($percentage >= 100 && $budgetCategory->alert_at_100_percent && ! $this->hasRecentAlert($budgetCategory, 100)) {
                $alerts[] = [
                    'type' => 'exceeded_limit',
                    'category' => $category->name,
                    'percentage' => $percentage,
                    'allocated' => $budgetCategory->allocated_amount,
                    'spent' => $budgetCategory->spent_amount,
                    'overspent' => $budgetCategory->spent_amount - $budgetCategory->allocated_amount,
                ];

                $this->markAlertSent($budgetCategory);
            }
        }

        return $alerts;
    }

    protected function hasRecentAlert(BudgetCategory $budgetCategory, int $threshold): bool
    {
        if (! $budgetCategory->last_alert_sent) {
            return false;
        }

        // Don't send same alert within 24 hours
        return $budgetCategory->last_alert_sent->diffInHours(now()) < 24;
    }

    protected function markAlertSent(BudgetCategory $budgetCategory): void
    {
        $budgetCategory->update(['last_alert_sent' => now()]);
    }

    public function activate(): void
    {
        $this->update(['status' => 'active']);
    }

    public function complete(): void
    {
        $this->update(['status' => 'completed']);
    }

    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    public function addCategory(int $categoryId, float $allocatedAmount, array $options = []): void
    {
        $this->budgetCategories()->create([
            'category_id' => $categoryId,
            'allocated_amount' => $allocatedAmount,
            'alert_at_80_percent' => $options['alert_at_80_percent'] ?? true,
            'alert_at_100_percent' => $options['alert_at_100_percent'] ?? true,
        ]);
    }

    public function removeCategory(int $categoryId): void
    {
        $this->budgetCategories()->where('category_id', $categoryId)->delete();
    }

    public function updateCategoryAllocation(int $categoryId, float $allocatedAmount): void
    {
        $this->budgetCategories()
            ->where('category_id', $categoryId)
            ->update(['allocated_amount' => $allocatedAmount]);
    }

    public static function getCurrentBudgets()
    {
        return static::active()->current()->with(['budgetCategories.category'])->get();
    }

    public static function processAutomaticUpdates(): void
    {
        // Update spent amounts for all active budgets
        static::active()->chunk(10, function ($budgets) {
            foreach ($budgets as $budget) {
                $budget->updateSpentAmounts();
            }
        });

        // Mark expired budgets as completed
        static::expired()->update(['status' => 'completed']);
    }
}
