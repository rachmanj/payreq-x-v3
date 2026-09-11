<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $viewActivityCosting = Permission::firstOrCreate(
            ['name' => 'view_activity_costing', 'guard_name' => 'web'],
            [],
        );

        $accTeam = Role::where('name', 'acc-team')->first();
        if ($accTeam && ! $accTeam->hasPermissionTo($viewActivityCosting)) {
            $accTeam->givePermissionTo($viewActivityCosting);
        }

        Artisan::call('permission:cache-reset');
    }

    public function down(): void
    {
        $viewActivityCosting = Permission::where('name', 'view_activity_costing')->where('guard_name', 'web')->first();

        if ($viewActivityCosting) {
            $accTeam = Role::where('name', 'acc-team')->first();
            if ($accTeam && $accTeam->hasPermissionTo($viewActivityCosting)) {
                $accTeam->revokePermissionTo($viewActivityCosting);
            }
        }

        Artisan::call('permission:cache-reset');
    }
};
