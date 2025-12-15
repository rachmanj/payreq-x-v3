<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class ProjectsDepartmentsPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // Projects Management
            [
                'name' => 'projects.view',
                'description' => 'View projects list'
            ],
            [
                'name' => 'sap-sync-projects',
                'description' => 'Sync projects from SAP B1'
            ],
            [
                'name' => 'projects.manage-visibility',
                'description' => 'Toggle project visibility (is_selectable)'
            ],
            // Departments Management
            [
                'name' => 'departments.view',
                'description' => 'View departments list'
            ],
            [
                'name' => 'sap-sync-departments',
                'description' => 'Sync departments from SAP B1'
            ],
            [
                'name' => 'departments.manage-visibility',
                'description' => 'Toggle department visibility (is_selectable)'
            ],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name']],
                ['guard_name' => 'web']
            );
        }

        $this->command->info('Projects and Departments permissions created successfully.');
    }
}
