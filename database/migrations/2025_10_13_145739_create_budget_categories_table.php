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
        Schema::create('budget_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->decimal('allocated_amount', 15, 2);
            $table->decimal('spent_amount', 15, 2)->default(0);
            $table->decimal('remaining_amount', 15, 2)->virtualAs('allocated_amount - spent_amount');
            $table->decimal('percentage_used', 5, 2)->virtualAs('CASE WHEN allocated_amount > 0 THEN (spent_amount / allocated_amount) * 100 ELSE 0 END');
            $table->boolean('alert_at_80_percent')->default(true);
            $table->boolean('alert_at_100_percent')->default(true);
            $table->datetime('last_alert_sent')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['budget_id', 'category_id']);
            $table->index(['budget_id', 'allocated_amount']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budget_categories');
    }
};
