<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            'akses_ap_invoice_bpjs',
            'submit_sap_ap_invoice_bpjs',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                [],
            );
        }

        foreach (['superadmin', 'manager', 'acc-team'] as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                foreach ($permissions as $name) {
                    $permission = Permission::where('name', $name)->where('guard_name', 'web')->first();
                    if ($permission && ! $role->hasPermissionTo($permission)) {
                        $role->givePermissionTo($permission);
                    }
                }
            }
        }

        Artisan::call('permission:cache-reset');
    }

    public function down(): void
    {
        $permissions = Permission::whereIn('name', [
            'akses_ap_invoice_bpjs',
            'submit_sap_ap_invoice_bpjs',
        ])->where('guard_name', 'web')->get();

        foreach ($permissions as $permission) {
            foreach (['superadmin', 'manager', 'acc-team'] as $roleName) {
                $role = Role::where('name', $roleName)->first();
                if ($role) {
                    $role->revokePermissionTo($permission);
                }
            }

            $permission->delete();
        }

        Artisan::call('permission:cache-reset');
    }
};
