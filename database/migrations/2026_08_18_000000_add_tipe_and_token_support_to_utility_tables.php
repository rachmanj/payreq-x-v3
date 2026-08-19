<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('utility_customers', function (Blueprint $table) {
            $table->string('tipe', 20)->default('postpaid')->after('jenis_utilitas');
            $table->index(['jenis_utilitas', 'tipe']);
        });

        Schema::table('utility_bills', function (Blueprint $table) {
            $table->index(['utility_customer_id', 'periode'], 'utility_bills_customer_periode_index');
            $table->dropUnique('utility_bills_utility_customer_id_periode_unique');
            $table->string('nomor_token')->nullable()->after('nomor_tagihan');
            $table->date('tanggal_jatuh_tempo')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('utility_bills', function (Blueprint $table) {
            $table->date('tanggal_jatuh_tempo')->nullable(false)->change();
            $table->dropColumn('nomor_token');
            // add unique FIRST (FK on utility_customer_id needs an index), then drop the regular index
            $table->unique(['utility_customer_id', 'periode']);
            $table->dropIndex('utility_bills_customer_periode_index');
        });

        Schema::table('utility_customers', function (Blueprint $table) {
            $table->dropIndex(['jenis_utilitas', 'tipe']);
            $table->dropColumn('tipe');
        });
    }
};
