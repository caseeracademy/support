<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update existing tickets to match new statuses
        DB::table('tickets')->where('status', 'open')->update(['status' => 'processing']);
        DB::table('tickets')->where('status', 'pending')->update(['status' => 'on-hold']);
        DB::table('tickets')->where('status', 'closed')->update(['status' => 'completed']);
        // 'resolved' stays as 'resolved' - this is our custom status
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to old statuses
        DB::table('tickets')->where('status', 'processing')->update(['status' => 'open']);
        DB::table('tickets')->where('status', 'on-hold')->update(['status' => 'pending']);
        DB::table('tickets')->where('status', 'completed')->update(['status' => 'closed']);
    }
};
