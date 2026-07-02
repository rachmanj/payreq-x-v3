<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DeleteKoranPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permission = Permission::firstOrCreate(
            ['name' => 'delete_koran'],
            ['guard_name' => 'web'],
        );

        foreach (['superadmin', 'admin', 'cashier'] as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role && ! $role->hasPermissionTo($permission)) {
                $role->givePermissionTo($permission);
            }
        }

        $this->command?->info('Permission delete_koran created; assigned to superadmin, admin, and cashier by default.');
    }
}
