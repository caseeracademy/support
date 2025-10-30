<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'employee_id',
        'first_name',
        'last_name',
        'email',
        'phone_number',
        'date_of_birth',
        'hire_date',
        'termination_date',
        'employment_type',
        'status',
        'department',
        'position',
        'reports_to',
        'base_salary',
        'salary_currency',
        'pay_frequency',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'emergency_contact_name',
        'emergency_contact_phone',
        'benefits',
        'documents',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'hire_date' => 'date',
            'termination_date' => 'date',
            'base_salary' => 'decimal:2',
            'benefits' => 'array',
            'documents' => 'array',
            'metadata' => 'array',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Employee $employee): void {
            if (empty($employee->employee_id)) {
                $employee->employee_id = $employee->generateEmployeeId();
            }
        });
    }

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'reports_to');
    }

    public function subordinates(): HasMany
    {
        return $this->hasMany(Employee::class, 'reports_to');
    }

    public function payrolls(): HasMany
    {
        return $this->hasMany(Payroll::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByDepartment($query, string $department)
    {
        return $query->where('department', $department);
    }

    public function scopeFullTime($query)
    {
        return $query->where('employment_type', 'full_time');
    }

    public function scopePartTime($query)
    {
        return $query->where('employment_type', 'part_time');
    }

    // Computed Properties
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active';
    }

    public function getTenureInMonthsAttribute(): int
    {
        $endDate = $this->termination_date ?? now();

        return $this->hire_date->diffInMonths($endDate);
    }

    public function getTenureInYearsAttribute(): float
    {
        return round($this->tenure_in_months / 12, 1);
    }

    public function getFormattedBaseSalaryAttribute(): string
    {
        return '$'.number_format($this->base_salary, 2).' '.$this->salary_currency;
    }

    public function getAgeAttribute(): ?int
    {
        if (! $this->date_of_birth) {
            return null;
        }

        return $this->date_of_birth->diffInYears(now());
    }

    // Methods
    public function generateEmployeeId(): string
    {
        $year = now()->format('Y');
        $lastEmployee = static::withTrashed()
            ->where('employee_id', 'like', "EMP{$year}%")
            ->orderBy('employee_id', 'desc')
            ->first();

        if ($lastEmployee) {
            $lastNumber = (int) substr($lastEmployee->employee_id, -4);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return "EMP{$year}".str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    public function calculateMonthlyPay(array $additions = [], array $deductions = []): float
    {
        $basePay = match ($this->pay_frequency) {
            'weekly' => $this->base_salary * 52 / 12,
            'biweekly' => $this->base_salary * 26 / 12,
            'semimonthly' => $this->base_salary * 24 / 12,
            'monthly' => $this->base_salary,
            default => $this->base_salary,
        };

        $totalAdditions = array_sum($additions);
        $totalDeductions = array_sum($deductions);

        return $basePay + $totalAdditions - $totalDeductions;
    }

    public function terminate(Carbon $terminationDate, ?string $reason = null): void
    {
        $this->update([
            'status' => 'terminated',
            'termination_date' => $terminationDate,
            'metadata' => array_merge($this->metadata ?? [], [
                'termination_reason' => $reason,
                'terminated_at' => now()->toDateTimeString(),
            ]),
        ]);
    }

    public function reactivate(): void
    {
        $this->update([
            'status' => 'active',
            'termination_date' => null,
        ]);
    }

    public function getMonthlyAttendanceSummary(int $year, int $month): array
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $attendances = $this->attendances()
            ->whereBetween('attendance_date', [$startDate, $endDate])
            ->get();

        return [
            'total_days' => $attendances->count(),
            'present_days' => $attendances->where('status', 'present')->count(),
            'absent_days' => $attendances->where('status', 'absent')->count(),
            'leave_days' => $attendances->where('status', 'leave')->count(),
            'half_days' => $attendances->where('status', 'half_day')->count(),
            'total_hours' => $attendances->sum('hours_worked'),
            'overtime_hours' => $attendances->sum('overtime_hours'),
        ];
    }

    public static function getDepartments(): array
    {
        return static::distinct()->pluck('department')->filter()->sort()->values()->toArray();
    }

    public static function getPositions(): array
    {
        return static::distinct()->pluck('position')->filter()->sort()->values()->toArray();
    }
}
