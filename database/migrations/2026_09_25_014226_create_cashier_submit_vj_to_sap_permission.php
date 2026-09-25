<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

return new class extends Migration
{
    public function up(): void
    {
        Artisan::call('db:seed', [
            '--class' => 'Database\\Seeders\\CashierSubmitVjToSapPermissionSeeder',
            '--force' => true,
        ]);

        Artisan::call('permission:cache-reset');
    }

    public function down(): void
    {
        $permission = \Spatie\Permission\Models\Permission::where('name', 'cashier_submit_vj_to_sap')
            ->where('guard_name', 'web')
            ->first();

        if ($permission) {
            $cashierRole = \Spatie\Permission\Models\Role::where('name', 'cashier')->first();
            if ($cashierRole) {
                $cashierRole->revokePermissionTo($permission);
            }

            foreach ([81, 130, 141] as $userId) {
                $user = \App\Models\User::query()->find($userId);
                if ($user) {
                    $user->revokePermissionTo($permission);
                }
            }

            $permission->delete();
        }

        Artisan::call('permission:cache-reset');
    }
};
