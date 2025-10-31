<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class PaymentMethod extends Model
{
    /** @use HasFactory<\Database\Factories\PaymentMethodFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'type',
        'metadata',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'is_active' => 'boolean',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (PaymentMethod $paymentMethod): void {
            if (empty($paymentMethod->slug)) {
                $paymentMethod->slug = Str::slug($paymentMethod->name);
            }
        });

        static::updating(function (PaymentMethod $paymentMethod): void {
            if ($paymentMethod->isDirty('name') && empty($paymentMethod->getOriginal('slug'))) {
                $paymentMethod->slug = Str::slug($paymentMethod->name);
            }
        });
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function recurringTransactions(): HasMany
    {
        return $this->hasMany(RecurringTransaction::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->name.($this->type ? " ({$this->type})" : '');
    }
}
