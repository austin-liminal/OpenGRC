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
        // Create Survey permissions
        $actions = ['List', 'Create', 'Read', 'Update', 'Delete'];

        foreach ($actions as $action) {
            Permission::firstOrCreate(
                ['name' => "{$action} Surveys", 'guard_name' => 'web'],
                ['category' => 'Surveys']
            );
        }

        // Create SurveyTemplate permissions
        foreach ($actions as $action) {
            Permission::firstOrCreate(
                ['name' => "{$action} SurveyTemplates", 'guard_name' => 'web'],
                ['category' => 'Surveys']
            );
        }

        // Get the roles
        $superAdmin = Role::where('name', 'Super Admin')->first();
        $securityAdmin = Role::where('name', 'Security Admin')->first();

        // Get the permissions
        $surveyPermissions = Permission::where('category', 'Surveys')->get();

        // Assign all Survey permissions to Super Admin
        if ($superAdmin) {
            $superAdmin->givePermissionTo($surveyPermissions);
        }

        // Assign all Survey permissions to Security Admin
        if ($securityAdmin) {
            $securityAdmin->givePermissionTo($surveyPermissions);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove Survey permissions
        $actions = ['List', 'Create', 'Read', 'Update', 'Delete'];

        foreach ($actions as $action) {
            Permission::where('name', "{$action} Surveys")->delete();
            Permission::where('name', "{$action} SurveyTemplates")->delete();
        }
    }
};
