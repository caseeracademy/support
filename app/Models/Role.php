<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'color',
        'is_system_role',
        'sort_order',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_system_role' => 'boolean',
            'sort_order' => 'integer',
            'metadata' => 'array',
        ];
    }

    // Relationships
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    // Methods
    public function givePermissionTo(Permission|string $permission): void
    {
        if (is_string($permission)) {
            $permission = Permission::where('name', $permission)->firstOrFail();
        }

        $this->permissions()->syncWithoutDetaching([$permission->id]);
    }

    public function revokePermissionTo(Permission|string $permission): void
    {
        if (is_string($permission)) {
            $permission = Permission::where('name', $permission)->first();
        }

        if ($permission) {
            $this->permissions()->detach($permission->id);
        }
    }

    public function hasPermission(string $permissionName): bool
    {
        return $this->permissions()->where('name', $permissionName)->exists();
    }

    public function syncPermissions(array $permissionIds): void
    {
        $this->permissions()->sync($permissionIds);
    }

    // Static methods for system roles
    public static function createSystemRoles(): void
    {
        $roles = [
            [
                'name' => 'admin',
                'display_name' => 'Administrator',
                'description' => 'Full access to all features and settings',
                'color' => '#DC2626',
                'is_system_role' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'customer_care',
                'display_name' => 'Customer Care',
                'description' => 'Manage support tickets and customer inquiries',
                'color' => '#0891B2',
                'is_system_role' => true,
                'sort_order' => 2,
            ],
        ];

        foreach ($roles as $roleData) {
            static::firstOrCreate(
                ['name' => $roleData['name']],
                $roleData
            );
        }
    }
}
