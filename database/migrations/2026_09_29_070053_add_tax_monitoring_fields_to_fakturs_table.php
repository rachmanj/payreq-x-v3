<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fakturs', function (Blueprint $table) {
            $table->char('masa_pajak', 7)->nullable()->after('faktur_date');
            $table->decimal('ppn_rate', 5, 2)->nullable()->after('ppn');
            $table->decimal('dpp_calculated', 20, 2)->nullable()->after('ppn_rate');
            $table->enum('dpp_source', ['gl', 'manual', 'formula'])->nullable()->after('dpp_calculated');
            $table->string('npwp_lawan', 25)->nullable()->after('dpp_source');
            $table->enum('validation_status', ['belum_diperiksa', 'valid', 'tidak_valid', 'diganti'])
                ->default('belum_diperiksa')
                ->after('npwp_lawan');
            $table->enum('coretax_status', ['belum_diketahui', 'approved', 'reject', 'diganti'])
                ->default('belum_diketahui')
                ->after('validation_status');
            $table->string('matched_doc_num', 30)->nullable()->after('coretax_status');
            $table->timestamp('matched_at')->nullable()->after('matched_doc_num');
        });

        Schema::table('fakturs', function (Blueprint $table) {
            // BUKAN unique: satu nomor faktur pajak sah dipakai banyak baris (satu faktur menutup
            // beberapa dokumen internal). Duplikat dideteksi lewat daftar periksa, bukan dilarang DB.
            $table->index(['type', 'faktur_no'], 'fakturs_type_faktur_no_index');
        });
    }

    public function down(): void
    {
        Schema::table('fakturs', function (Blueprint $table) {
            $table->dropIndex('fakturs_type_faktur_no_index');
            $table->dropColumn([
                'masa_pajak',
                'ppn_rate',
                'dpp_calculated',
                'dpp_source',
                'npwp_lawan',
                'validation_status',
                'coretax_status',
                'matched_doc_num',
                'matched_at',
            ]);
        });
    }
};
