<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bpjs_ap_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('jenis');
            $table->string('unit');
            $table->string('periode');
            $table->decimal('amount', 15, 2);
            $table->date('doc_date');
            $table->date('due_date');
            $table->string('num_at_card');
            $table->string('label');
            $table->string('status')->default('pending');
            $table->string('sap_doc_num')->nullable();
            $table->unsignedBigInteger('sap_doc_entry')->nullable();
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->date('paid_at')->nullable();
            $table->text('sap_error_message')->nullable();
            $table->unsignedBigInteger('submitted_by')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index(['jenis', 'unit', 'periode']);
            $table->index('num_at_card');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bpjs_ap_invoices');
    }
};
