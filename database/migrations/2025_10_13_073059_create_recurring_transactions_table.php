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
        Schema::create('recurring_transactions', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['income', 'expense']);
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('title');
            $table->text('description')->nullable();
            
            // Foreign keys
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_method_id')->constrained()->cascadeOnDelete();
            
            // Polymorphic relationship
            $table->nullableMorphs('recurrable');
            
            // Recurrence configuration
            $table->enum('frequency', ['daily', 'weekly', 'monthly', 'quarterly', 'yearly']);
            $table->integer('interval')->default(1); // Every X frequency (e.g., every 2 months)
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->integer('max_occurrences')->nullable();
            
            // Status and tracking
            $table->boolean('is_active')->default(true);
            $table->date('next_due_date');
            $table->integer('occurrences_created')->default(0);
            $table->timestamp('last_processed_at')->nullable();
            
            // Additional configuration
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['is_active', 'next_due_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recurring_transactions');
    }
};
