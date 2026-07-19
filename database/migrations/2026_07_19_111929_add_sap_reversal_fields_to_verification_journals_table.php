<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('verification_journals', function (Blueprint $table) {
            $table->string('sap_je_jdt_num')->nullable()->after('sap_journal_no');
            $table->timestamp('sap_reversed_at')->nullable()->after('sap_submitted_by');
            $table->foreignId('sap_reversed_by')->nullable()->after('sap_reversed_at');
            $table->text('sap_reversal_reason')->nullable()->after('sap_reversed_by');
            $table->string('sap_reversal_journal_no')->nullable()->after('sap_reversal_reason');
        });
    }

    public function down(): void
    {
        Schema::table('verification_journals', function (Blueprint $table) {
            $table->dropColumn([
                'sap_je_jdt_num',
                'sap_reversed_at',
                'sap_reversed_by',
                'sap_reversal_reason',
                'sap_reversal_journal_no',
            ]);
        });
    }
};
