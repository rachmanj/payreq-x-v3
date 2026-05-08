<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sap_gl_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_reconciliation_id')->constrained('bank_reconciliations')->cascadeOnDelete();
            $table->date('doc_date')->nullable();
            $table->date('posting_date')->nullable();
            $table->string('doc_num', 64)->nullable();
            $table->string('ref_doc_num', 128)->nullable();
            $table->string('transaction_id', 64)->nullable();
            $table->text('description')->nullable();
            $table->string('project_code', 64)->nullable();
            $table->decimal('debit', 18, 2)->default(0);
            $table->decimal('credit', 18, 2)->default(0);
            $table->string('matched_status', 32)->default('unmatched');
            $table->timestamps();

            $table->index(['bank_reconciliation_id', 'matched_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sap_gl_lines');
    }
};
