<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ArInvoicePermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permission = Permission::firstOrCreate(
            ['name' => 'submit-sap-ar-invoice'],
            ['guard_name' => 'web']
        );

        // Assign permission to superadmin role
        $superadminRole = Role::where('name', 'superadmin')->first();
        if ($superadminRole && !$superadminRole->hasPermissionTo($permission)) {
            $superadminRole->givePermissionTo($permission);
        }

        $this->command->info('AR Invoice SAP submission permission created and assigned to superadmin successfully.');
    }
}
