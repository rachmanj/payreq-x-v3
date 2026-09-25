<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CashierSubmitVjToSapPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permission = Permission::firstOrCreate(
            ['name' => 'cashier_submit_vj_to_sap'],
            ['guard_name' => 'web'],
        );

        $cashierRole = Role::where('name', 'cashier')->first();
        if ($cashierRole && ! $cashierRole->hasPermissionTo($permission)) {
            $cashierRole->givePermissionTo($permission);
        }

        foreach ([81, 130, 141] as $userId) {
            $user = User::query()->find($userId);
            if ($user && ! $user->hasPermissionTo($permission)) {
                $user->givePermissionTo($permission);
            }
        }

        $this->command?->info('Permission cashier_submit_vj_to_sap created; assigned to cashier role and users 81, 130, 141 when present.');
    }
}
