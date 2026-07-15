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
            ['name' => 'akses_sap_sync', 'guard_name' => 'web'],
            [],
        );

        $roles = Role::query()->whereIn('name', ['approver_bo', 'cashier_bo'])->get();

        foreach ($roles as $role) {
            if (! $role->hasPermissionTo($permission)) {
                $role->givePermissionTo($permission);
            }
        }

        Artisan::call('permission:cache-reset');
    }

    public function down(): void
    {
        $roles = Role::query()->whereIn('name', ['approver_bo', 'cashier_bo'])->get();

        foreach ($roles as $role) {
            $role->revokePermissionTo('akses_sap_sync');
        }

        Artisan::call('permission:cache-reset');
    }
};
