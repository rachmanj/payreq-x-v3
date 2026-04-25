<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ValidatePcbcReportPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permission = Permission::firstOrCreate(
            ['name' => 'validate_pcbc_report'],
            ['guard_name' => 'web'],
        );

        foreach (['superadmin', 'admin'] as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role && ! $role->hasPermissionTo($permission)) {
                $role->givePermissionTo($permission);
            }
        }

        $this->command->info('Permission validate_pcbc_report created; assigned to superadmin and admin by default.');
    }
}
