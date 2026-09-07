<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Respon SAP B1 lengkap (dokumen utuh) bisa >64KB -> TEXT (64KB) meluap:
        // 1406 Data too long utk kolom sap_response / error_message saat submit AP Invoice
        // (kasus nyata: TELKOM Sep 2026, doc 267007299, 2026-09-07). MEDIUMTEXT = 16MB.
        Schema::table('sap_submission_logs', function (Blueprint $table) {
            $table->mediumText('error_message')->nullable()->change();
            $table->mediumText('sap_response')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('sap_submission_logs', function (Blueprint $table) {
            $table->text('error_message')->nullable()->change();
            $table->text('sap_response')->nullable()->change();
        });
    }
};
