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
            ['name' => 'cashier_dashboard', 'guard_name' => 'web'],
            [],
        );

        foreach (['admin', 'superadmin'] as $roleName) {
            $role = Role::query()->where('name', $roleName)->first();

            if ($role && ! $role->hasPermissionTo($permission)) {
                $role->givePermissionTo($permission);
            }
        }

        Artisan::call('permission:cache-reset');
    }

    public function down(): void
    {
        $permission = Permission::query()
            ->where('name', 'cashier_dashboard')
            ->where('guard_name', 'web')
            ->first();

        if ($permission) {
            foreach (['admin', 'superadmin'] as $roleName) {
                $role = Role::query()->where('name', $roleName)->first();

                if ($role && $role->hasPermissionTo($permission)) {
                    $role->revokePermissionTo($permission);
                }
            }
        }

        Artisan::call('permission:cache-reset');
    }
};
