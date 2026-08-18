<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AksesUtilitiesPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permission = Permission::firstOrCreate(
            ['name' => 'akses_utilities'],
            ['guard_name' => 'web'],
        );

        foreach (['superadmin', 'manager'] as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role && ! $role->hasPermissionTo($permission)) {
                $role->givePermissionTo($permission);
            }
        }

        $this->command->info('Permission akses_utilities created; assigned to superadmin and manager by default.');
    }
}
