<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RecalculateCashierBalancePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permission = Permission::firstOrCreate(
            ['name' => 'recalculate_cashier_balance'],
            ['guard_name' => 'web'],
        );

        foreach (['admin', 'superadmin'] as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role && ! $role->hasPermissionTo($permission)) {
                $role->givePermissionTo($permission);
            }
        }

        $this->command?->info('Permission recalculate_cashier_balance created; assigned to admin and superadmin roles when present.');
    }
}
