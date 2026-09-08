<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $newPermissions = [
            'submit_sap_ap_invoice_installment',
            'submit_sap_op_installment',
        ];

        foreach ($newPermissions as $name) {
            Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                [],
            );
        }

        $rolesWithNewPermissions = [
            'approver',
            'admin',
            'cashier',
            'approver_bo',
            'cashier_bo',
        ];

        foreach ($rolesWithNewPermissions as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                foreach ($newPermissions as $name) {
                    $permission = Permission::where('name', $name)->where('guard_name', 'web')->first();
                    if ($permission && ! $role->hasPermissionTo($permission)) {
                        $role->givePermissionTo($permission);
                    }
                }
            }
        }

        $loanReportRoles = ['approver', 'cashier_bo'];
        $loanReportPermission = Permission::where('name', 'akses_loan_report')->where('guard_name', 'web')->first();

        if ($loanReportPermission) {
            foreach ($loanReportRoles as $roleName) {
                $role = Role::where('name', $roleName)->first();
                if ($role && ! $role->hasPermissionTo($loanReportPermission)) {
                    $role->givePermissionTo($loanReportPermission);
                }
            }
        }

        Artisan::call('permission:cache-reset');
    }

    public function down(): void
    {
        $newPermissions = Permission::whereIn('name', [
            'submit_sap_ap_invoice_installment',
            'submit_sap_op_installment',
        ])->where('guard_name', 'web')->get();

        foreach ($newPermissions as $permission) {
            foreach (['approver', 'admin', 'cashier', 'approver_bo', 'cashier_bo'] as $roleName) {
                $role = Role::where('name', $roleName)->first();
                if ($role) {
                    $role->revokePermissionTo($permission);
                }
            }

            $permission->delete();
        }

        $loanReportPermission = Permission::where('name', 'akses_loan_report')->where('guard_name', 'web')->first();
        if ($loanReportPermission) {
            foreach (['approver', 'cashier_bo'] as $roleName) {
                $role = Role::where('name', $roleName)->first();
                if ($role && $role->hasPermissionTo($loanReportPermission)) {
                    $role->revokePermissionTo($loanReportPermission);
                }
            }
        }

        Artisan::call('permission:cache-reset');
    }
};
