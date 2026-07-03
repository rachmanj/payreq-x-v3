<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = ['akses_notulen', 'upload_notulen', 'delete_notulen'];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                [],
            );
        }

        $roles = Role::query()->whereIn('name', ['superadmin', 'admin'])->get();

        foreach ($roles as $role) {
            $role->givePermissionTo($permissions);
        }

        Artisan::call('permission:cache-reset');
    }

    public function down(): void
    {
        $permissions = ['akses_notulen', 'upload_notulen', 'delete_notulen'];

        $roles = Role::query()->whereIn('name', ['superadmin', 'admin'])->get();

        foreach ($roles as $role) {
            $role->revokePermissionTo($permissions);
        }

        Permission::query()
            ->whereIn('name', $permissions)
            ->where('guard_name', 'web')
            ->delete();

        Artisan::call('permission:cache-reset');
    }
};
