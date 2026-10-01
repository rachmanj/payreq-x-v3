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
            ['name' => 'view_login_audit', 'guard_name' => 'web'],
            [],
        );

        $role = Role::query()->where('name', 'superadmin')->first();
        if ($role && ! $role->hasPermissionTo($permission)) {
            $role->givePermissionTo($permission);
        }

        Artisan::call('permission:cache-reset');
    }

    public function down(): void
    {
        $role = Role::query()->where('name', 'superadmin')->first();
        if ($role) {
            $role->revokePermissionTo('view_login_audit');
        }

        Permission::query()->where('name', 'view_login_audit')->where('guard_name', 'web')->delete();

        Artisan::call('permission:cache-reset');
    }
};
