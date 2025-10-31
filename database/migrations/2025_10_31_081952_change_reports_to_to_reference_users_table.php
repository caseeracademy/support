<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop the existing foreign key constraint
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['reports_to']);
        });

        // Change the foreign key to reference users table instead
        Schema::table('employees', function (Blueprint $table) {
            $table->foreign('reports_to')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the foreign key to users
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['reports_to']);
        });

        // Restore the foreign key to employees
        Schema::table('employees', function (Blueprint $table) {
            $table->foreign('reports_to')
                ->references('id')
                ->on('employees')
                ->onDelete('set null');
        });
    }
};
