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
            ['name' => 'akses_help', 'guard_name' => 'web'],
            [],
        );

        $roles = Role::query()->whereIn('name', ['superadmin', 'admin', 'cashier'])->get();

        foreach ($roles as $role) {
            $role->givePermissionTo($permission);
        }

        Artisan::call('permission:cache-reset');
    }

    public function down(): void
    {
        $roles = Role::query()->whereIn('name', ['superadmin', 'admin', 'cashier'])->get();

        foreach ($roles as $role) {
            $role->revokePermissionTo('akses_help');
        }

        Permission::query()->where('name', 'akses_help')->where('guard_name', 'web')->delete();

        Artisan::call('permission:cache-reset');
    }
};
