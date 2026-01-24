<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

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
        // Create permissions for PolicyExceptions
        $actions = ['List', 'Create', 'Read', 'Update', 'Delete'];

        foreach ($actions as $action) {
            Permission::firstOrCreate([
                'name' => "{$action} PolicyExceptions",
                'category' => 'PolicyExceptions',
            ]);
        }

        // Get existing roles
        $superAdmin = Role::where('name', 'Super Admin')->first();
        $securityAdmin = Role::where('name', 'Security Admin')->first();
        $regular = Role::where('name', 'Regular User')->first();

        // Assign all permissions to Super Admin
        if ($superAdmin) {
            foreach ($actions as $action) {
                $superAdmin->givePermissionTo("{$action} PolicyExceptions");
            }
        }

        // Assign List, Create, Read, Update to Security Admin (same as other entities)
        if ($securityAdmin) {
            foreach (['List', 'Create', 'Read', 'Update'] as $action) {
                $securityAdmin->givePermissionTo("{$action} PolicyExceptions");
            }
        }

        // Assign List, Read to Regular User (same as other entities)
        if ($regular) {
            foreach (['List', 'Read'] as $action) {
                $regular->givePermissionTo("{$action} PolicyExceptions");
            }
        }
    }
}
