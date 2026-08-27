<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sap_submission_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('dds_invoice_id')->nullable()->after('utility_ap_invoice_id');
            $table->string('dds_invoice_number', 100)->nullable()->after('dds_invoice_id');
            $table->index('dds_invoice_id');
        });
    }

    public function down(): void
    {
        Schema::table('sap_submission_logs', function (Blueprint $table) {
            $table->dropIndex(['dds_invoice_id']);
            $table->dropColumn(['dds_invoice_id', 'dds_invoice_number']);
        });
    }
};
