<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $permission = Permission::firstOrCreate(
            ['name' => 'anggaran_bulk_activate_deactivate'],
            ['guard_name' => 'web'],
        );

        $roles = Role::query()->whereHas('permissions', function ($q): void {
            $q->where('name', 'recalculate_release');
        })->get();

        foreach ($roles as $role) {
            $role->givePermissionTo($permission);
        }
    }

    public function down(): void
    {
        Permission::query()->where('name', 'anggaran_bulk_activate_deactivate')->delete();
    }
};
