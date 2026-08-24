<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('utility_bills', function (Blueprint $table) {
            $table->unsignedBigInteger('utility_ap_invoice_id')->nullable()->after('payreq_id')->index();
            $table->foreign('utility_ap_invoice_id')->references('id')->on('utility_ap_invoices')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('utility_bills', function (Blueprint $table) {
            $table->dropForeign(['utility_ap_invoice_id']);
            $table->dropColumn('utility_ap_invoice_id');
        });
    }
};
