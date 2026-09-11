<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $permission = Permission::firstOrCreate(
            ['name' => 'manage_activities', 'guard_name' => 'web'],
            [],
        );

        foreach (['superadmin', 'admin', 'approver', 'approver_bo'] as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role && ! $role->hasPermissionTo($permission)) {
                $role->givePermissionTo($permission);
            }
        }

        Artisan::call('permission:cache-reset');
    }

    public function down(): void
    {
        $permission = Permission::where('name', 'manage_activities')->where('guard_name', 'web')->first();

        if ($permission) {
            foreach (['approver', 'approver_bo'] as $roleName) {
                $role = Role::where('name', $roleName)->first();
                if ($role && $role->hasPermissionTo($permission)) {
                    $role->revokePermissionTo($permission);
                }
            }
        }

        Artisan::call('permission:cache-reset');
    }
};
