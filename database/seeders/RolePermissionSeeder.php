<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating roles...');
        Role::createSystemRoles();

        $this->command->info('Creating permissions...');
        Permission::createDefaultPermissions();

        $this->command->info('Assigning permissions to roles...');
        Permission::assignDefaultPermissions();

        $this->command->info('✓ Roles and permissions setup complete!');
    }
}
