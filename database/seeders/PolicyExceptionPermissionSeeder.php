<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PolicyExceptionPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates CRUD permissions for PolicyExceptions and assigns them to roles
     * following the same pattern as Assets.
     */
    public function run(): void
    {
        // Reset cached permissions before starting
        app()->make(PermissionRegistrar::class)->forgetCachedPermissions();

        // Create permissions for PolicyExceptions
        $actions = ['List', 'Create', 'Read', 'Update', 'Delete'];
        $permissions = [];

        foreach ($actions as $action) {
            $permissions[$action] = Permission::firstOrCreate(
                ['name' => "{$action} PolicyExceptions", 'guard_name' => 'web'],
                ['category' => 'PolicyExceptions']
            );
        }

        // Get existing roles
        $superAdmin = Role::where('name', 'Super Admin')->where('guard_name', 'web')->first();
        $securityAdmin = Role::where('name', 'Security Admin')->where('guard_name', 'web')->first();
        $regular = Role::where('name', 'Regular User')->where('guard_name', 'web')->first();

        // Assign all permissions to Super Admin
        if ($superAdmin) {
            foreach ($permissions as $permission) {
                if (! $superAdmin->hasPermissionTo($permission)) {
                    $superAdmin->givePermissionTo($permission);
                }
            }
        }

        // Assign List, Create, Read, Update to Security Admin (same as other entities)
        if ($securityAdmin) {
            foreach (['List', 'Create', 'Read', 'Update'] as $action) {
                if (! $securityAdmin->hasPermissionTo($permissions[$action])) {
                    $securityAdmin->givePermissionTo($permissions[$action]);
                }
            }
        }

        // Assign List, Read to Regular User (same as other entities)
        if ($regular) {
            foreach (['List', 'Read'] as $action) {
                if (! $regular->hasPermissionTo($permissions[$action])) {
                    $regular->givePermissionTo($permissions[$action]);
                }
            }
        }

        // Clear cache after assigning
        app()->make(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
