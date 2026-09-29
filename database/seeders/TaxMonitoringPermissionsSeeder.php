<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class TaxMonitoringPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'view_tax_monitoring',
            'manage_tax_monitoring',
            'approve_tax_period',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(
                ['name' => $name],
                ['guard_name' => 'web'],
            );
        }

        $view = Permission::findByName('view_tax_monitoring', 'web');
        $manage = Permission::findByName('manage_tax_monitoring', 'web');
        $approve = Permission::findByName('approve_tax_period', 'web');

        foreach (['superadmin', 'acc-team'] as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role && ! $role->hasPermissionTo($view)) {
                $role->givePermissionTo($view);
            }
        }

        foreach (['superadmin'] as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                if (! $role->hasPermissionTo($manage)) {
                    $role->givePermissionTo($manage);
                }
                if (! $role->hasPermissionTo($approve)) {
                    $role->givePermissionTo($approve);
                }
            }
        }

        $this->command?->info('Tax monitoring permissions created; view assigned to superadmin and acc-team.');
    }
}
