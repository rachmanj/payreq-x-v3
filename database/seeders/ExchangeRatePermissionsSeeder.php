<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class ExchangeRatePermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define exchange rate permissions
        $permissions = [
            [
                'name' => 'akses_exchange_rates',
                'description' => 'Access exchange rates module'
            ],
            [
                'name' => 'create_exchange_rates',
                'description' => 'Create new exchange rates'
            ],
            [
                'name' => 'edit_exchange_rates',
                'description' => 'Edit existing exchange rates and perform bulk updates'
            ],
            [
                'name' => 'delete_exchange_rates',
                'description' => 'Delete exchange rates and perform bulk deletes'
            ],
            [
                'name' => 'import_exchange_rates',
                'description' => 'Import exchange rates from Excel files'
            ],
            [
                'name' => 'export_exchange_rates',
                'description' => 'Export exchange rates to Excel files'
            ],
        ];

        // Create permissions if they don't exist
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name']],
                ['guard_name' => 'web']
            );
        }

        $this->command->info('Exchange rate permissions created successfully.');
    }
}
