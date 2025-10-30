<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'group',
    ];

    // Relationships
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    // Static methods for creating default permissions
    public static function createDefaultPermissions(): void
    {
        $permissions = [
            // Financial Permissions
            ['name' => 'finance.view', 'display_name' => 'View Financial Data', 'group' => 'finance'],
            ['name' => 'finance.create', 'display_name' => 'Create Transactions', 'group' => 'finance'],
            ['name' => 'finance.edit', 'display_name' => 'Edit Transactions', 'group' => 'finance'],
            ['name' => 'finance.delete', 'display_name' => 'Delete Transactions', 'group' => 'finance'],
            ['name' => 'finance.approve', 'display_name' => 'Approve Transactions', 'group' => 'finance'],
            ['name' => 'finance.reports', 'display_name' => 'Generate Financial Reports', 'group' => 'finance'],
            ['name' => 'finance.export', 'display_name' => 'Export Financial Data', 'group' => 'finance'],

            // Invoice Permissions
            ['name' => 'invoice.view', 'display_name' => 'View Invoices', 'group' => 'invoice'],
            ['name' => 'invoice.create', 'display_name' => 'Create Invoices', 'group' => 'invoice'],
            ['name' => 'invoice.edit', 'display_name' => 'Edit Invoices', 'group' => 'invoice'],
            ['name' => 'invoice.delete', 'display_name' => 'Delete Invoices', 'group' => 'invoice'],
            ['name' => 'invoice.send', 'display_name' => 'Send Invoices', 'group' => 'invoice'],

            // Budget Permissions
            ['name' => 'budget.view', 'display_name' => 'View Budgets', 'group' => 'budget'],
            ['name' => 'budget.create', 'display_name' => 'Create Budgets', 'group' => 'budget'],
            ['name' => 'budget.edit', 'display_name' => 'Edit Budgets', 'group' => 'budget'],
            ['name' => 'budget.delete', 'display_name' => 'Delete Budgets', 'group' => 'budget'],

            // HR Permissions
            ['name' => 'employee.view', 'display_name' => 'View Employees', 'group' => 'hr'],
            ['name' => 'employee.create', 'display_name' => 'Create Employees', 'group' => 'hr'],
            ['name' => 'employee.edit', 'display_name' => 'Edit Employees', 'group' => 'hr'],
            ['name' => 'employee.delete', 'display_name' => 'Delete Employees', 'group' => 'hr'],
            ['name' => 'employee.terminate', 'display_name' => 'Terminate Employees', 'group' => 'hr'],

            // Payroll Permissions
            ['name' => 'payroll.view', 'display_name' => 'View Payroll', 'group' => 'payroll'],
            ['name' => 'payroll.create', 'display_name' => 'Create Payroll', 'group' => 'payroll'],
            ['name' => 'payroll.edit', 'display_name' => 'Edit Payroll', 'group' => 'payroll'],
            ['name' => 'payroll.approve', 'display_name' => 'Approve Payroll', 'group' => 'payroll'],
            ['name' => 'payroll.process', 'display_name' => 'Process Payments', 'group' => 'payroll'],

            // Support Permissions
            ['name' => 'ticket.view', 'display_name' => 'View Tickets', 'group' => 'support'],
            ['name' => 'ticket.create', 'display_name' => 'Create Tickets', 'group' => 'support'],
            ['name' => 'ticket.edit', 'display_name' => 'Edit Tickets', 'group' => 'support'],
            ['name' => 'ticket.delete', 'display_name' => 'Delete Tickets', 'group' => 'support'],
            ['name' => 'ticket.assign', 'display_name' => 'Assign Tickets', 'group' => 'support'],

            // Customer Permissions
            ['name' => 'customer.view', 'display_name' => 'View Customers', 'group' => 'customer'],
            ['name' => 'customer.create', 'display_name' => 'Create Customers', 'group' => 'customer'],
            ['name' => 'customer.edit', 'display_name' => 'Edit Customers', 'group' => 'customer'],
            ['name' => 'customer.delete', 'display_name' => 'Delete Customers', 'group' => 'customer'],

            // Student Permissions
            ['name' => 'student.view', 'display_name' => 'View Students', 'group' => 'student'],
            ['name' => 'student.create', 'display_name' => 'Create Students', 'group' => 'student'],
            ['name' => 'student.edit', 'display_name' => 'Edit Students', 'group' => 'student'],

            // System Permissions
            ['name' => 'user.view', 'display_name' => 'View Users', 'group' => 'system'],
            ['name' => 'user.create', 'display_name' => 'Create Users', 'group' => 'system'],
            ['name' => 'user.edit', 'display_name' => 'Edit Users', 'group' => 'system'],
            ['name' => 'user.delete', 'display_name' => 'Delete Users', 'group' => 'system'],
            ['name' => 'settings.manage', 'display_name' => 'Manage Settings', 'group' => 'system'],
        ];

        foreach ($permissions as $permissionData) {
            static::firstOrCreate(
                ['name' => $permissionData['name']],
                $permissionData
            );
        }
    }

    public static function assignToRole(string $roleName, array $permissionNames): void
    {
        $role = Role::where('name', $roleName)->first();
        if (! $role) {
            return;
        }

        $permissions = static::whereIn('name', $permissionNames)->get();
        $role->permissions()->syncWithoutDetaching($permissions->pluck('id'));
    }

    // Define default permission sets for each role
    public static function assignDefaultPermissions(): void
    {
        // Admin - Full access
        static::assignToRole('admin', static::all()->pluck('name')->toArray());

        // Accounting - Finance, budgets, payroll, invoices
        static::assignToRole('accounting', [
            'finance.view', 'finance.create', 'finance.edit', 'finance.delete', 'finance.approve', 'finance.reports', 'finance.export',
            'invoice.view', 'invoice.create', 'invoice.edit', 'invoice.delete', 'invoice.send',
            'budget.view', 'budget.create', 'budget.edit', 'budget.delete',
            'payroll.view', 'payroll.create', 'payroll.edit', 'payroll.approve', 'payroll.process',
            'customer.view',
        ]);

        // Sales - Customers, tickets, invoices
        static::assignToRole('sales', [
            'customer.view', 'customer.create', 'customer.edit',
            'ticket.view', 'ticket.create', 'ticket.edit',
            'invoice.view', 'invoice.create',
            'finance.view',
            'student.view',
        ]);

        // Marketing - View access, customer management
        static::assignToRole('marketing', [
            'customer.view', 'customer.create', 'customer.edit',
            'student.view',
            'finance.view', 'finance.reports',
            'ticket.view',
        ]);

        // HR - Employees, payroll, attendance
        static::assignToRole('hr', [
            'employee.view', 'employee.create', 'employee.edit', 'employee.delete', 'employee.terminate',
            'payroll.view', 'payroll.create', 'payroll.edit', 'payroll.approve', 'payroll.process',
        ]);

        // Support - Tickets and customers
        static::assignToRole('support', [
            'ticket.view', 'ticket.create', 'ticket.edit', 'ticket.assign',
            'customer.view', 'customer.create', 'customer.edit',
            'student.view',
        ]);

        // Viewer - Read-only
        static::assignToRole('viewer', [
            'finance.view', 'finance.reports',
            'invoice.view',
            'budget.view',
            'ticket.view',
            'customer.view',
            'employee.view',
            'payroll.view',
            'student.view',
        ]);
    }
}
