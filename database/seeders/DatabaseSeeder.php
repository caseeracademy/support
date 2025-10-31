<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed roles first (required for employees)
        $this->command->info('Creating system roles...');

        // Seed finance-related data
        $this->call([
            CategorySeeder::class,
            PaymentMethodSeeder::class,
        ]);

        // Seed employees with their user accounts and roles
        $this->command->info('Creating employees and user accounts...');
        $this->call([
            EmployeeSeeder::class,
        ]);

        $this->command->info('Database seeding completed! ✅');
    }
}
