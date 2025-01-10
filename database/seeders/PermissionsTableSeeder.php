<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Permission::where('name', 'akses_delivery')->delete();
        Permission::create([
            'name' => 'akses_delivery',
            'guard_name' => 'web',
        ]);
    }
}
