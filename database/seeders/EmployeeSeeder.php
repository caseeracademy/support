<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure roles exist
        Role::createSystemRoles();

        // Create Customer Care Agent 1
        $ccUser1 = User::firstOrCreate(
            ['email' => 'jane.doe@caseer.academy'],
            [
                'name' => 'Jane Doe',
                'password' => bcrypt('password'),
            ]
        );
        $ccUser1->syncRoles([Role::where('name', 'customer_care')->first()]);

        Employee::firstOrCreate(
            ['email' => 'jane.doe@caseer.academy'],
            [
                'user_id' => $ccUser1->id,
                'first_name' => 'Jane',
                'last_name' => 'Doe',
                'phone_number' => '+1 (555) 100-0001',
                'date_of_birth' => '1990-06-15',
                'hire_date' => now()->subMonths(12),
                'employment_type' => 'full_time',
                'status' => 'active',
                'department' => 'Customer Support',
                'position' => 'Customer Care Agent',
                'base_salary' => 42000,
                'salary_currency' => 'USD',
                'pay_frequency' => 'monthly',
                'city' => 'New York',
                'state' => 'NY',
                'country' => 'US',
            ]
        );

        // Create Customer Care Agent 2
        $ccUser2 = User::firstOrCreate(
            ['email' => 'john.smith@caseer.academy'],
            [
                'name' => 'John Smith',
                'password' => bcrypt('password'),
            ]
        );
        $ccUser2->syncRoles([Role::where('name', 'customer_care')->first()]);

        Employee::firstOrCreate(
            ['email' => 'john.smith@caseer.academy'],
            [
                'user_id' => $ccUser2->id,
                'first_name' => 'John',
                'last_name' => 'Smith',
                'phone_number' => '+1 (555) 100-0002',
                'date_of_birth' => '1992-03-22',
                'hire_date' => now()->subMonths(8),
                'employment_type' => 'full_time',
                'status' => 'active',
                'department' => 'Customer Support',
                'position' => 'Customer Care Agent',
                'base_salary' => 42000,
                'salary_currency' => 'USD',
                'pay_frequency' => 'monthly',
                'city' => 'Los Angeles',
                'state' => 'CA',
                'country' => 'US',
            ]
        );

        $this->command->info('✅ Created 2 Customer Care employees with @caseer.academy email!');
        $this->command->info('📧 Both have login accounts (password: password)');
        $this->command->info('👤 Emails: jane.doe@caseer.academy, john.smith@caseer.academy');
    }
}
