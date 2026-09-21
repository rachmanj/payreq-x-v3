<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bpjs_ap_invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('bpjs_ap_invoices', 'sap_document_status')) {
                $table->string('sap_document_status', 20)->nullable()->after('sap_doc_entry');
            }
            if (! Schema::hasColumn('bpjs_ap_invoices', 'sap_cancelled')) {
                $table->boolean('sap_cancelled')->nullable()->after('sap_document_status');
            }
            if (! Schema::hasColumn('bpjs_ap_invoices', 'sap_status_synced_at')) {
                $table->timestamp('sap_status_synced_at')->nullable()->after('sap_cancelled');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bpjs_ap_invoices', function (Blueprint $table) {
            $columns = [];

            if (Schema::hasColumn('bpjs_ap_invoices', 'sap_status_synced_at')) {
                $columns[] = 'sap_status_synced_at';
            }
            if (Schema::hasColumn('bpjs_ap_invoices', 'sap_cancelled')) {
                $columns[] = 'sap_cancelled';
            }
            if (Schema::hasColumn('bpjs_ap_invoices', 'sap_document_status')) {
                $columns[] = 'sap_document_status';
            }

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
