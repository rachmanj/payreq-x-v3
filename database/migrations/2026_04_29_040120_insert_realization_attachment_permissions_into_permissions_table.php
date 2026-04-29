<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $names = [
            'akses_realization_attachments',
            'create_realization_attachments',
            'delete_realization_attachments',
            'realization_attachments_scope_bo',
        ];

        foreach ($names as $name) {
            Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                [],
            );
        }

        Artisan::call('permission:cache-reset');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Permission::whereIn('name', [
            'akses_realization_attachments',
            'create_realization_attachments',
            'delete_realization_attachments',
            'realization_attachments_scope_bo',
        ])->delete();

        Artisan::call('permission:cache-reset');
    }
};
