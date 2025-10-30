<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->string('payroll_period'); // e.g., "2024-01" for January 2024
            $table->date('period_start_date');
            $table->date('period_end_date');
            $table->date('payment_date');
            $table->decimal('base_pay', 15, 2);
            $table->decimal('overtime_pay', 15, 2)->default(0);
            $table->decimal('bonus', 15, 2)->default(0);
            $table->decimal('commission', 15, 2)->default(0);
            $table->decimal('allowances', 15, 2)->default(0);
            $table->decimal('gross_pay', 15, 2); // Total before deductions
            $table->decimal('tax_deduction', 15, 2)->default(0);
            $table->decimal('insurance_deduction', 15, 2)->default(0);
            $table->decimal('retirement_deduction', 15, 2)->default(0);
            $table->decimal('other_deductions', 15, 2)->default(0);
            $table->decimal('total_deductions', 15, 2);
            $table->decimal('net_pay', 15, 2); // Take home amount
            $table->string('currency', 3)->default('USD');
            $table->decimal('hours_worked', 8, 2)->nullable();
            $table->decimal('overtime_hours', 8, 2)->nullable();
            $table->enum('status', ['draft', 'pending_approval', 'approved', 'paid', 'cancelled'])->default('draft');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->datetime('approved_at')->nullable();
            $table->foreignId('transaction_id')->nullable()->constrained()->onDelete('set null'); // Link to transaction
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'payroll_period']);
            $table->index(['status', 'payment_date']);
            $table->index(['period_start_date', 'period_end_date']);
            $table->unique(['employee_id', 'payroll_period']); // One payroll per employee per period
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};
