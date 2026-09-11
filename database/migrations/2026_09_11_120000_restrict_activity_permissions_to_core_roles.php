<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /** @var list<string> */
    private array $coreRoles = ['superadmin', 'admin', 'approver'];

    public function up(): void
    {
        $manageActivities = Permission::firstOrCreate(
            ['name' => 'manage_activities', 'guard_name' => 'web'],
            [],
        );

        $viewActivityCosting = Permission::firstOrCreate(
            ['name' => 'view_activity_costing', 'guard_name' => 'web'],
            [],
        );

        foreach ($this->coreRoles as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role && ! $role->hasPermissionTo($manageActivities)) {
                $role->givePermissionTo($manageActivities);
            }
            if ($role && ! $role->hasPermissionTo($viewActivityCosting)) {
                $role->givePermissionTo($viewActivityCosting);
            }
        }

        foreach (Role::all() as $role) {
            if (in_array($role->name, $this->coreRoles, true)) {
                continue;
            }

            if ($role->hasPermissionTo($manageActivities)) {
                $role->revokePermissionTo($manageActivities);
            }

            if ($role->hasPermissionTo($viewActivityCosting)) {
                $role->revokePermissionTo($viewActivityCosting);
            }
        }

        Artisan::call('permission:cache-reset');
    }

    public function down(): void
    {
        $manageActivities = Permission::where('name', 'manage_activities')->where('guard_name', 'web')->first();
        $viewActivityCosting = Permission::where('name', 'view_activity_costing')->where('guard_name', 'web')->first();

        if ($manageActivities) {
            $approverBo = Role::where('name', 'approver_bo')->first();
            if ($approverBo && ! $approverBo->hasPermissionTo($manageActivities)) {
                $approverBo->givePermissionTo($manageActivities);
            }
        }

        if ($viewActivityCosting) {
            $accTeam = Role::where('name', 'acc-team')->first();
            if ($accTeam && ! $accTeam->hasPermissionTo($viewActivityCosting)) {
                $accTeam->givePermissionTo($viewActivityCosting);
            }

            $approver = Role::where('name', 'approver')->first();
            if ($approver && $approver->hasPermissionTo($viewActivityCosting)) {
                $approver->revokePermissionTo($viewActivityCosting);
            }
        }

        Artisan::call('permission:cache-reset');
    }
};
