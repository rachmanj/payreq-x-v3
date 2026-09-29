<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fakturs', function (Blueprint $table) {
            $table->enum('sync_source', ['sap_auto', 'excel_manual', 'manual'])->default('manual')->after('batch_no');
            $table->unsignedBigInteger('sap_trans_id')->nullable()->after('sync_source');
            $table->unsignedInteger('sap_line_id')->nullable()->after('sap_trans_id');

            $table->unique(['sap_trans_id', 'sap_line_id'], 'fakturs_sap_journal_line_unique');
        });
    }

    public function down(): void
    {
        Schema::table('fakturs', function (Blueprint $table) {
            $table->dropUnique('fakturs_sap_journal_line_unique');
            $table->dropColumn(['sync_source', 'sap_trans_id', 'sap_line_id']);
        });
    }
};
