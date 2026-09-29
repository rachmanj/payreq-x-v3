<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Sesuai spec monitoring PPN (keputusan Iwan, 29 Sep 2026):
 *   - lihat (view_tax_monitoring)   -> acc-team
 *   - kelola/tandai (manage_tax_monitoring) -> acc-team (tim pajak ada di dalamnya)
 *   - setujui + tutup masa (approve_tax_period) -> pejabat yang ditunjuk (menunggu nama dari Iwan)
 *
 * Migrasi ini menambahkan `manage_tax_monitoring` ke role acc-team. `view_tax_monitoring`
 * sudah diberikan pada migrasi 2026_09_29_070054.
 */
return new class extends Migration
{
    public function up(): void
    {
        $permission = Permission::where('name', 'manage_tax_monitoring')->first();

        if (! $permission) {
            return;
        }

        foreach (['acc-team'] as $roleName) {
            $role = Role::where('name', $roleName)->first();

            if ($role && ! $role->hasPermissionTo($permission->name)) {
                $role->givePermissionTo($permission);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $permission = Permission::where('name', 'manage_tax_monitoring')->first();
        $role = Role::where('name', 'acc-team')->first();

        if ($permission && $role && $role->hasPermissionTo($permission->name)) {
            $role->revokePermissionTo($permission);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
