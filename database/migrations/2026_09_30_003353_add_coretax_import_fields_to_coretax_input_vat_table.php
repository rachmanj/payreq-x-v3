<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coretax_input_vat', function (Blueprint $table) {
            $table->decimal('nilai_bruto', 20, 2)->nullable()->after('faktur_date');
            $table->char('masa_pengkreditan', 7)->nullable()->after('status_faktur');
            $table->string('perekam', 150)->nullable()->after('masa_pengkreditan');
            $table->string('referensi', 100)->nullable()->after('perekam');
            $table->boolean('valid_coretax')->nullable()->after('referensi');
            $table->boolean('dilaporkan')->nullable()->after('valid_coretax');
        });
    }

    public function down(): void
    {
        Schema::table('coretax_input_vat', function (Blueprint $table) {
            $table->dropColumn([
                'nilai_bruto',
                'masa_pengkreditan',
                'perekam',
                'referensi',
                'valid_coretax',
                'dilaporkan',
            ]);
        });
    }
};
