<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bpjs_ap_invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('bpjs_ap_invoices', 'sap_previous_doc_num')) {
                $table->string('sap_previous_doc_num', 50)->nullable()->after('sap_doc_num');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bpjs_ap_invoices', function (Blueprint $table) {
            if (Schema::hasColumn('bpjs_ap_invoices', 'sap_previous_doc_num')) {
                $table->dropColumn('sap_previous_doc_num');
            }
        });
    }
};
