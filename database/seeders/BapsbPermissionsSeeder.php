<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class BapsbPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $menuPermission = Permission::firstOrCreate(
            ['name' => 'akses_bapsb'],
            ['guard_name' => 'web'],
        );

        $validatePermission = Permission::firstOrCreate(
            ['name' => 'validate_bapsb_report'],
            ['guard_name' => 'web'],
        );

        foreach (['superadmin', 'admin', 'cashier', 'head_cashier'] as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                if (! $role->hasPermissionTo($menuPermission)) {
                    $role->givePermissionTo($menuPermission);
                }
            }
        }

        $headCashier = Role::where('name', 'head_cashier')->first();
        if ($headCashier && ! $headCashier->hasPermissionTo($validatePermission)) {
            $headCashier->givePermissionTo($validatePermission);
        }

        foreach ([13, 45, 11, 17, 112] as $userId) {
            $user = User::query()->find($userId);
            if ($user) {
                if (! $user->hasPermissionTo($validatePermission)) {
                    $user->givePermissionTo($validatePermission);
                }
            }
        }

        foreach (['superadmin', 'admin'] as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role && ! $role->hasPermissionTo($validatePermission)) {
                $role->givePermissionTo($validatePermission);
            }
        }

        $this->command?->info('BAPSB permissions created and assigned.');
    }
}
