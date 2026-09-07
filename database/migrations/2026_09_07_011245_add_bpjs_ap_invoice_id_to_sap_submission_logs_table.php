<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sap_submission_logs', function (Blueprint $table) {
            $table->foreignId('bpjs_ap_invoice_id')
                ->nullable()
                ->after('utility_ap_invoice_id')
                ->constrained('bpjs_ap_invoices')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sap_submission_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('bpjs_ap_invoice_id');
        });
    }
};
