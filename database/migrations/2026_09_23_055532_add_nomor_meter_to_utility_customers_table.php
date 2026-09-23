<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('utility_customers', function (Blueprint $table) {
            $table->string('nomor_meter', 50)->nullable()->after('id_pelanggan');
            $table->index('nomor_meter');
        });
    }

    public function down(): void
    {
        Schema::table('utility_customers', function (Blueprint $table) {
            $table->dropIndex(['nomor_meter']);
            $table->dropColumn('nomor_meter');
        });
    }
};
