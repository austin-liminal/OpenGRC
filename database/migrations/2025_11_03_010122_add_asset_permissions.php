<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create Asset permissions
        $assetActions = ['List', 'Create', 'Read', 'Update', 'Delete'];
        foreach ($assetActions as $action) {
            Permission::firstOrCreate([
                'name' => "{$action} Assets",
                'category' => 'Assets',
            ]);
        }

        // Get the roles
        $regular = Role::where('name', 'Regular User')->first();
        $superAdmin = Role::where('name', 'Super Admin')->first();
        $securityAdmin = Role::where('name', 'Security Admin')->first();
        $internalAuditor = Role::where('name', 'Internal Auditor')->first();

        // Get the permissions
        $assetPermissions = Permission::where('category', 'Assets')->get();

        // Assign permissions to Super Admin (all permissions)
        if ($superAdmin) {
            $superAdmin->givePermissionTo($assetPermissions);
        }

        // Assign permissions to Regular User (List and Read only)
        if ($regular) {
            $regular->givePermissionTo([
                'List Assets',
                'Read Assets',
            ]);
        }

        // Assign permissions to Security Admin (List, Create, Read, Update - no Delete)
        if ($securityAdmin) {
            $securityAdmin->givePermissionTo([
                'List Assets',
                'Create Assets',
                'Read Assets',
                'Update Assets',
            ]);
        }

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove Asset permissions
        $assetActions = ['List', 'Create', 'Read', 'Update', 'Delete'];
        foreach ($assetActions as $action) {
            Permission::where('name', "{$action} Assets")->delete();
        }
    }
};
