<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('utility_ap_invoices', function (Blueprint $table) {
            $table->date('paid_at')->nullable()->after('submitted_at');
            $table->decimal('paid_amount', 15, 2)->nullable()->after('paid_at');
            $table->string('paid_sap_doc_num', 50)->nullable()->after('paid_amount');
            $table->unsignedBigInteger('paid_sap_doc_entry')->nullable()->after('paid_sap_doc_num');
            $table->foreignId('paid_by')->nullable()->after('paid_sap_doc_entry')->constrained('users')->nullOnDelete();
            $table->text('payment_remarks')->nullable()->after('paid_by');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE utility_ap_invoices MODIFY status ENUM('pending','posted','failed','paid') NOT NULL DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        Schema::table('utility_ap_invoices', function (Blueprint $table) {
            $table->dropForeign(['paid_by']);
            $table->dropColumn([
                'paid_at',
                'paid_amount',
                'paid_sap_doc_num',
                'paid_sap_doc_entry',
                'paid_by',
                'payment_remarks',
            ]);
        });
    }
};
