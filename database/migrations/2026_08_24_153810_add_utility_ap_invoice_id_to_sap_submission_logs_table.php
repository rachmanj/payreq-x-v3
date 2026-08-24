<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sap_submission_logs', function (Blueprint $table) {
            $table->foreignId('utility_ap_invoice_id')->nullable()->after('journal_entry_id');
        });

        Schema::table('sap_submission_logs', function (Blueprint $table) {
            $table->foreign('utility_ap_invoice_id')->references('id')->on('utility_ap_invoices')->nullOnDelete();
            $table->index('utility_ap_invoice_id');
        });
    }

    public function down(): void
    {
        Schema::table('sap_submission_logs', function (Blueprint $table) {
            $table->dropForeign(['utility_ap_invoice_id']);
            $table->dropColumn('utility_ap_invoice_id');
        });
    }
};
