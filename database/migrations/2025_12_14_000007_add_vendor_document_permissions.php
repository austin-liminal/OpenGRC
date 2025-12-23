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
        $actions = ['List', 'Create', 'Read', 'Update', 'Delete'];

        // Create VendorDocument permissions (uses Survey permissions via policy,
        // but we add these for explicit permission management if needed)
        foreach ($actions as $action) {
            Permission::firstOrCreate(
                ['name' => "{$action} VendorDocuments", 'guard_name' => 'web'],
                ['category' => 'Vendor Management']
            );
        }

        // Ensure Vendor permissions exist
        foreach ($actions as $action) {
            Permission::firstOrCreate(
                ['name' => "{$action} Vendors", 'guard_name' => 'web'],
                ['category' => 'Vendor Management']
            );
        }

        // Ensure Survey permissions exist (may already exist from previous migration)
        foreach ($actions as $action) {
            Permission::firstOrCreate(
                ['name' => "{$action} Surveys", 'guard_name' => 'web'],
                ['category' => 'Surveys']
            );
        }

        // Ensure SurveyTemplate permissions exist (may already exist from previous migration)
        foreach ($actions as $action) {
            Permission::firstOrCreate(
                ['name' => "{$action} SurveyTemplates", 'guard_name' => 'web'],
                ['category' => 'Surveys']
            );
        }

        // Get the roles
        $superAdmin = Role::where('name', 'Super Admin')->first();
        $securityAdmin = Role::where('name', 'Security Admin')->first();

        // Get all relevant permissions
        $vendorPermissions = Permission::where('category', 'Vendor Management')->get();
        $surveyPermissions = Permission::where('category', 'Surveys')->get();

        // Assign permissions to Super Admin
        if ($superAdmin) {
            $superAdmin->givePermissionTo($vendorPermissions);
            $superAdmin->givePermissionTo($surveyPermissions);
        }

        // Assign permissions to Security Admin
        if ($securityAdmin) {
            $securityAdmin->givePermissionTo($vendorPermissions);
            $securityAdmin->givePermissionTo($surveyPermissions);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $actions = ['List', 'Create', 'Read', 'Update', 'Delete'];

        foreach ($actions as $action) {
            Permission::where('name', "{$action} VendorDocuments")->delete();
        }
    }
};
