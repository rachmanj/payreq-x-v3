<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $permission = Permission::firstOrCreate(
            ['name' => 'submit_sap_utility_payment', 'guard_name' => 'web'],
            [],
        );

        $sourcePermission = Permission::where('name', 'submit_sap_ap_invoice_utilities')
            ->where('guard_name', 'web')
            ->first();

        if ($sourcePermission) {
            $roleIds = DB::table('role_has_permissions')
                ->where('permission_id', $sourcePermission->id)
                ->pluck('role_id');

            foreach ($roleIds as $roleId) {
                $role = Role::find($roleId);
                if ($role && ! $role->hasPermissionTo($permission)) {
                    $role->givePermissionTo($permission);
                }
            }
        } else {
            foreach (['superadmin', 'manager', 'acc-team'] as $roleName) {
                $role = Role::where('name', $roleName)->first();
                if ($role && ! $role->hasPermissionTo($permission)) {
                    $role->givePermissionTo($permission);
                }
            }
        }

        Artisan::call('permission:cache-reset');
    }

    public function down(): void
    {
        $permission = Permission::where('name', 'submit_sap_utility_payment')->where('guard_name', 'web')->first();

        if ($permission) {
            $roleIds = DB::table('role_has_permissions')
                ->where('permission_id', $permission->id)
                ->pluck('role_id');

            foreach ($roleIds as $roleId) {
                $role = Role::find($roleId);
                if ($role) {
                    $role->revokePermissionTo($permission);
                }
            }

            $permission->delete();
        }

        Artisan::call('permission:cache-reset');
    }
};
