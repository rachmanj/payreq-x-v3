<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        Artisan::call('db:seed', [
            '--class' => 'Database\\Seeders\\TaxMonitoringPermissionsSeeder',
            '--force' => true,
        ]);

        Artisan::call('permission:cache-reset');
    }

    public function down(): void
    {
        $names = ['view_tax_monitoring', 'manage_tax_monitoring', 'approve_tax_period'];

        foreach ($names as $name) {
            $permission = Permission::where('name', $name)->where('guard_name', 'web')->first();
            if (! $permission) {
                continue;
            }

            foreach (Role::all() as $role) {
                $role->revokePermissionTo($permission);
            }

            $permission->delete();
        }

        Artisan::call('permission:cache-reset');
    }
};
