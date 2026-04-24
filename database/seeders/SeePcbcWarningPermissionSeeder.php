<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SeePcbcWarningPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permission = Permission::firstOrCreate(
            ['name' => 'see_pcbc_warning'],
            ['guard_name' => 'web'],
        );

        foreach (['superadmin', 'admin', 'Cashier', 'cashier'] as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role && ! $role->hasPermissionTo($permission)) {
                $role->givePermissionTo($permission);
            }
        }

        $this->command->info('Permission see_pcbc_warning created; assigned to available roles (superadmin, admin, Cashier, cashier).');
    }
}
