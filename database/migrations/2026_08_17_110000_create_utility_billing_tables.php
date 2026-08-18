<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('utility_customers', function (Blueprint $table) {
            $table->id();
            $table->string('jenis_utilitas', 20);
            $table->string('id_pelanggan', 50);
            $table->string('nama');
            $table->string('lokasi')->nullable();
            $table->string('project', 20);
            $table->foreignId('account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['jenis_utilitas', 'id_pelanggan']);
            $table->index(['project', 'jenis_utilitas']);
        });

        Schema::create('utility_bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('utility_customer_id')->constrained('utility_customers')->cascadeOnDelete();
            $table->string('periode', 7);
            $table->decimal('jumlah_tagihan', 15, 2);
            $table->string('nomor_tagihan')->nullable();
            $table->date('tanggal_jatuh_tempo');
            $table->date('tanggal_bayar')->nullable();
            $table->integer('meter_awal')->nullable();
            $table->integer('meter_akhir')->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();
            $table->unique(['utility_customer_id', 'periode']);
            $table->index(['tanggal_jatuh_tempo', 'tanggal_bayar']);
        });

        $permission = Permission::firstOrCreate(['name' => 'akses_utilities', 'guard_name' => 'web']);
        foreach (['superadmin', 'manager'] as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role && ! $role->hasPermissionTo($permission)) {
                $role->givePermissionTo($permission);
            }
        }
        Artisan::call('permission:cache-reset');
    }

    public function down(): void
    {
        $permission = Permission::where('name', 'akses_utilities')->where('guard_name', 'web')->first();
        if ($permission) {
            foreach (['superadmin', 'manager'] as $roleName) {
                $role = Role::where('name', $roleName)->first();
                if ($role) {
                    $role->revokePermissionTo($permission);
                }
            }
            $permission->delete();
        }
        Schema::dropIfExists('utility_bills');
        Schema::dropIfExists('utility_customers');
        Artisan::call('permission:cache-reset');
    }
};
