<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payroll extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'payroll_period',
        'period_start_date',
        'period_end_date',
        'payment_date',
        'base_pay',
        'overtime_pay',
        'bonus',
        'commission',
        'allowances',
        'gross_pay',
        'tax_deduction',
        'insurance_deduction',
        'retirement_deduction',
        'other_deductions',
        'total_deductions',
        'net_pay',
        'currency',
        'hours_worked',
        'overtime_hours',
        'status',
        'approved_by',
        'approved_at',
        'transaction_id',
        'notes',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'period_start_date' => 'date',
            'period_end_date' => 'date',
            'payment_date' => 'date',
            'base_pay' => 'decimal:2',
            'overtime_pay' => 'decimal:2',
            'bonus' => 'decimal:2',
            'commission' => 'decimal:2',
            'allowances' => 'decimal:2',
            'gross_pay' => 'decimal:2',
            'tax_deduction' => 'decimal:2',
            'insurance_deduction' => 'decimal:2',
            'retirement_deduction' => 'decimal:2',
            'other_deductions' => 'decimal:2',
            'total_deductions' => 'decimal:2',
            'net_pay' => 'decimal:2',
            'hours_worked' => 'decimal:2',
            'overtime_hours' => 'decimal:2',
            'approved_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Payroll $payroll): void {
            if (empty($payroll->payroll_period)) {
                $payroll->payroll_period = $payroll->period_start_date->format('Y-m');
            }
        });

        static::saving(function (Payroll $payroll): void {
            // Auto-calculate gross pay
            $payroll->gross_pay = $payroll->base_pay +
                                 $payroll->overtime_pay +
                                 $payroll->bonus +
                                 $payroll->commission +
                                 $payroll->allowances;

            // Auto-calculate total deductions
            $payroll->total_deductions = $payroll->tax_deduction +
                                         $payroll->insurance_deduction +
                                         $payroll->retirement_deduction +
                                         $payroll->other_deductions;

            // Auto-calculate net pay
            $payroll->net_pay = $payroll->gross_pay - $payroll->total_deductions;
        });
    }

    // Relationships
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    // Scopes
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', ['draft', 'pending_approval']);
    }

    public function scopeByPeriod($query, string $period)
    {
        return $query->where('payroll_period', $period);
    }

    public function scopeByYear($query, int $year)
    {
        return $query->where('payroll_period', 'like', "{$year}-%");
    }

    // Computed Properties
    public function getFormattedGrossPayAttribute(): string
    {
        return '$'.number_format($this->gross_pay, 2).' '.$this->currency;
    }

    public function getFormattedNetPayAttribute(): string
    {
        return '$'.number_format($this->net_pay, 2).' '.$this->currency;
    }

    public function getFormattedTotalDeductionsAttribute(): string
    {
        return '$'.number_format($this->total_deductions, 2).' '.$this->currency;
    }

    public function getDeductionRateAttribute(): float
    {
        if ($this->gross_pay <= 0) {
            return 0;
        }

        return ($this->total_deductions / $this->gross_pay) * 100;
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'gray',
            'pending_approval' => 'warning',
            'approved' => 'info',
            'paid' => 'success',
            'cancelled' => 'danger',
            default => 'gray',
        };
    }

    // Methods
    public function approve(User $user): void
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);
    }

    public function markAsPaid(?Transaction $transaction = null): void
    {
        $updateData = [
            'status' => 'paid',
        ];

        if ($transaction) {
            $updateData['transaction_id'] = $transaction->id;
        }

        $this->update($updateData);
    }

    public function createPaymentTransaction(): Transaction
    {
        $transaction = Transaction::create([
            'type' => 'expense',
            'amount' => $this->net_pay,
            'currency' => $this->currency,
            'title' => "Payroll - {$this->employee->full_name} ({$this->payroll_period})",
            'description' => "Salary payment for {$this->payroll_period}",
            'transaction_date' => $this->payment_date,
            'category_id' => Category::firstOrCreate(
                ['name' => 'Payroll', 'type' => 'expense'],
                ['color' => '#EF4444', 'is_active' => true]
            )->id,
            'status' => 'completed',
            'processed_at' => now(),
            'created_by' => \Illuminate\Support\Facades\Auth::id(),
            'metadata' => [
                'payroll_id' => $this->id,
                'employee_id' => $this->employee_id,
                'employee_name' => $this->employee->full_name,
                'payroll_period' => $this->payroll_period,
            ],
        ]);

        $this->markAsPaid($transaction);

        return $transaction;
    }

    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    public static function generateForEmployee(
        Employee $employee,
        Carbon $periodStart,
        Carbon $periodEnd,
        array $data = []
    ): self {
        // Calculate base pay for the period
        $basePay = static::calculateBasePay($employee, $periodStart, $periodEnd);

        // Get attendance data for overtime calculation
        $attendanceSummary = $employee->getMonthlyAttendanceSummary(
            $periodStart->year,
            $periodStart->month
        );

        $overtimePay = ($data['overtime_hours'] ?? $attendanceSummary['overtime_hours']) *
                      ($employee->base_salary / 160) * 1.5; // Assuming 160 hours/month, 1.5x for overtime

        return static::create(array_merge([
            'employee_id' => $employee->id,
            'payroll_period' => $periodStart->format('Y-m'),
            'period_start_date' => $periodStart,
            'period_end_date' => $periodEnd,
            'payment_date' => $periodEnd->copy()->addDays(5), // Pay 5 days after period ends
            'base_pay' => $basePay,
            'overtime_pay' => $overtimePay,
            'hours_worked' => $attendanceSummary['total_hours'] ?? 0,
            'overtime_hours' => $attendanceSummary['overtime_hours'] ?? 0,
            'currency' => $employee->salary_currency,
            'status' => 'draft',
        ], $data));
    }

    protected static function calculateBasePay(Employee $employee, Carbon $periodStart, Carbon $periodEnd): float
    {
        $days = $periodStart->diffInDays($periodEnd) + 1;

        return match ($employee->pay_frequency) {
            'weekly' => ($employee->base_salary / 7) * $days,
            'biweekly' => ($employee->base_salary / 14) * $days,
            'semimonthly' => ($employee->base_salary / 15) * $days,
            'monthly' => $employee->base_salary,
            default => $employee->base_salary,
        };
    }
}
