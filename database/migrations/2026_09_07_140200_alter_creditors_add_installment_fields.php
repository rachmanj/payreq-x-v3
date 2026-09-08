<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('creditors', function (Blueprint $table) {
            $table->string('nama_singkat', 50)->nullable()->after('name');
            $table->string('akun_pokok_default', 20)->nullable()->after('nama_singkat');
        });
    }

    public function down(): void
    {
        Schema::table('creditors', function (Blueprint $table) {
            $table->dropColumn(['nama_singkat', 'akun_pokok_default']);
        });
    }
};
