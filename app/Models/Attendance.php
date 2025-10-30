<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'attendance_date',
        'clock_in',
        'clock_out',
        'hours_worked',
        'overtime_hours',
        'status',
        'leave_type',
        'notes',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'clock_in' => 'datetime',
            'clock_out' => 'datetime',
            'hours_worked' => 'decimal:2',
            'overtime_hours' => 'decimal:2',
            'metadata' => 'array',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (Attendance $attendance): void {
            // Auto-calculate hours worked if clock in/out are set
            if ($attendance->clock_in && $attendance->clock_out && ! $attendance->hours_worked) {
                $attendance->calculateHours();
            }
        });
    }

    // Relationships
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    // Scopes
    public function scopePresent($query)
    {
        return $query->where('status', 'present');
    }

    public function scopeAbsent($query)
    {
        return $query->where('status', 'absent');
    }

    public function scopeOnLeave($query)
    {
        return $query->where('status', 'leave');
    }

    public function scopeByDate($query, Carbon $date)
    {
        return $query->where('attendance_date', $date->format('Y-m-d'));
    }

    public function scopeByMonth($query, int $year, int $month)
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        return $query->whereBetween('attendance_date', [$startDate, $endDate]);
    }

    // Methods
    public function calculateHours(): void
    {
        if (! $this->clock_in || ! $this->clock_out) {
            return;
        }

        $clockIn = Carbon::parse($this->clock_in);
        $clockOut = Carbon::parse($this->clock_out);

        $totalHours = $clockIn->diffInMinutes($clockOut) / 60;

        // Standard working hours (8 hours)
        $standardHours = 8;
        $regularHours = min($totalHours, $standardHours);
        $overtimeHours = max(0, $totalHours - $standardHours);

        $this->hours_worked = round($regularHours, 2);
        $this->overtime_hours = round($overtimeHours, 2);
    }

    public function clockIn(?Carbon $time = null): void
    {
        $this->update([
            'clock_in' => $time ?? now(),
            'status' => 'present',
        ]);
    }

    public function clockOut(?Carbon $time = null): void
    {
        $this->update([
            'clock_out' => $time ?? now(),
        ]);

        $this->calculateHours();
        $this->save();
    }

    public function markAsLeave(string $leaveType = 'paid'): void
    {
        $this->update([
            'status' => 'leave',
            'leave_type' => $leaveType,
            'hours_worked' => 0,
            'overtime_hours' => 0,
        ]);
    }

    public function markAsAbsent(): void
    {
        $this->update([
            'status' => 'absent',
            'hours_worked' => 0,
            'overtime_hours' => 0,
        ]);
    }

    public function getFormattedHoursWorkedAttribute(): string
    {
        return number_format($this->hours_worked, 2).' hrs';
    }

    public function getFormattedOvertimeHoursAttribute(): string
    {
        if ($this->overtime_hours <= 0) {
            return '-';
        }

        return number_format($this->overtime_hours, 2).' hrs';
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'present' => 'success',
            'absent' => 'danger',
            'half_day' => 'warning',
            'leave' => 'info',
            'holiday' => 'gray',
            'sick' => 'warning',
            default => 'gray',
        };
    }

    public static function createOrUpdateForToday(Employee $employee, array $data): self
    {
        return static::updateOrCreate(
            [
                'employee_id' => $employee->id,
                'attendance_date' => now()->format('Y-m-d'),
            ],
            $data
        );
    }

    public static function bulkMarkPresent(array $employeeIds, Carbon $date): int
    {
        $count = 0;

        foreach ($employeeIds as $employeeId) {
            static::updateOrCreate(
                [
                    'employee_id' => $employeeId,
                    'attendance_date' => $date->format('Y-m-d'),
                ],
                [
                    'status' => 'present',
                    'hours_worked' => 8, // Standard day
                ]
            );
            $count++;
        }

        return $count;
    }
}
