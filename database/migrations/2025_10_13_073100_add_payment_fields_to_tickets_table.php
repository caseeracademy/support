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
        Schema::table('tickets', function (Blueprint $table) {
            // Payment status tracking
            $table->enum('payment_status', ['pending', 'paid', 'partial', 'refunded', 'cancelled'])->default('pending');
            $table->decimal('total_amount', 15, 2)->nullable();
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->string('currency', 3)->default('USD');

            // Order/payment reference
            $table->string('order_reference')->nullable();
            $table->string('payment_reference')->nullable();

            // Timestamps for payment tracking
            $table->timestamp('payment_due_date')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('payment_approved_at')->nullable();
            $table->foreignId('payment_approved_by')->nullable()->constrained('users')->nullOnDelete();

            // Additional payment metadata
            $table->json('payment_metadata')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn([
                'payment_status',
                'total_amount',
                'paid_amount',
                'currency',
                'order_reference',
                'payment_reference',
                'payment_due_date',
                'paid_at',
                'payment_approved_at',
                'payment_approved_by',
                'payment_metadata',
            ]);
        });
    }
};
