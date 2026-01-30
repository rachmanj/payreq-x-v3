<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('installments', function (Blueprint $table) {
            $table->string('sap_ap_doc_num')->nullable()->after('status');
            $table->integer('sap_ap_doc_entry')->nullable()->after('sap_ap_doc_num');
            $table->string('sap_payment_doc_num')->nullable()->after('sap_ap_doc_entry');
            $table->integer('sap_payment_doc_entry')->nullable()->after('sap_payment_doc_num');
            $table->enum('sap_sync_status', ['pending', 'ap_created', 'payment_created', 'completed'])
                ->default('pending')
                ->after('sap_payment_doc_entry');
            $table->text('sap_error_message')->nullable()->after('sap_sync_status');

            $table->index('sap_ap_doc_num');
            $table->index('sap_sync_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('installments', function (Blueprint $table) {
            $table->dropIndex(['sap_ap_doc_num']);
            $table->dropIndex(['sap_sync_status']);
            $table->dropColumn([
                'sap_ap_doc_num',
                'sap_ap_doc_entry',
                'sap_payment_doc_num',
                'sap_payment_doc_entry',
                'sap_sync_status',
                'sap_error_message',
            ]);
        });
    }
};
