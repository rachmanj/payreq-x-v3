<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->string('entity_code', 20)->default('000H')->after('creditor_id');
            $table->string('kode_unit', 50)->nullable()->after('entity_code');
            $table->string('project_code', 50)->nullable()->after('kode_unit');
            $table->decimal('total_bunga', 16, 2)->nullable()->after('principal');
            $table->string('rate_method', 10)->nullable()->after('total_bunga');
            $table->string('akun_pokok_gl', 20)->nullable()->after('rate_method');
            $table->string('costing_code', 20)->default('60')->after('akun_pokok_gl');
            $table->unsignedBigInteger('account_id')->nullable()->after('user_id');
            $table->index('account_id');
        });
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropIndex(['account_id']);
            $table->dropColumn([
                'entity_code',
                'kode_unit',
                'project_code',
                'total_bunga',
                'rate_method',
                'akun_pokok_gl',
                'costing_code',
                'account_id',
            ]);
        });
    }
};
