<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class RealizationAttachmentPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $names = [
            'akses_realization_attachments',
            'create_realization_attachments',
            'delete_realization_attachments',
            'realization_attachments_scope_bo',
        ];

        foreach ($names as $name) {
            Permission::firstOrCreate(
                ['name' => $name],
                ['guard_name' => 'web'],
            );
        }
    }
}
