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
            ['name' => 'akses_cashier_modal', 'guard_name' => 'web'],
            [],
        );

        $adminRole = Role::query()->where('name', 'admin')->first();

        if ($adminRole && ! $adminRole->hasPermissionTo($permission)) {
            $adminRole->givePermissionTo($permission);
        }

        Artisan::call('permission:cache-reset');
    }

    public function down(): void
    {
        $permission = Permission::query()
            ->where('name', 'akses_cashier_modal')
            ->where('guard_name', 'web')
            ->first();

        $adminRole = Role::query()->where('name', 'admin')->first();

        if ($permission && $adminRole && $adminRole->hasPermissionTo($permission)) {
            $adminRole->revokePermissionTo($permission);
        }

        Artisan::call('permission:cache-reset');
    }
};
