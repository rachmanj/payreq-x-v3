<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

return new class extends Migration
{
    public function up(): void
    {
        Artisan::call('db:seed', [
            '--class' => 'Database\\Seeders\\RecalculateCashierBalancePermissionSeeder',
            '--force' => true,
        ]);

        Artisan::call('permission:cache-reset');
    }

    public function down(): void
    {
        $permission = \Spatie\Permission\Models\Permission::where('name', 'recalculate_cashier_balance')
            ->where('guard_name', 'web')
            ->first();

        if ($permission) {
            foreach (['admin', 'superadmin'] as $roleName) {
                $role = \Spatie\Permission\Models\Role::where('name', $roleName)->first();
                if ($role) {
                    $role->revokePermissionTo($permission);
                }
            }

            $permission->delete();
        }

        Artisan::call('permission:cache-reset');
    }
};
