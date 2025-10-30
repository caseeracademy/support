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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['income', 'expense']);
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('transaction_date');
            
            // Foreign keys
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_method_id')->constrained()->cascadeOnDelete();
            
            // Polymorphic relationship - can link to customers, tickets, etc.
            $table->nullableMorphs('transactionable');
            
            // Payment status tracking
            $table->enum('status', ['pending', 'completed', 'cancelled', 'refunded'])->default('completed');
            $table->timestamp('processed_at')->nullable();
            
            // Reference information
            $table->string('reference_number')->nullable()->unique();
            $table->string('external_reference')->nullable(); // For external payment IDs
            
            // Additional metadata
            $table->json('metadata')->nullable();
            
            // Audit fields
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['type', 'status']);
            $table->index(['transaction_date', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
