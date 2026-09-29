<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ppn_input_sync', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('trans_id');
            $table->unsignedInteger('line_id');
            $table->string('document_no', 50)->nullable();
            $table->string('doc_type', 80)->nullable();
            $table->date('creation_date')->nullable();
            $table->date('posting_date')->nullable();
            $table->date('faktur_date')->nullable();
            $table->string('vendor_code', 50)->nullable();
            $table->string('vendor_name', 200)->nullable();
            $table->string('faktur_no', 50)->nullable();
            $table->decimal('amount', 20, 2);
            $table->string('project_code', 50)->nullable();
            $table->text('remark')->nullable();
            $table->string('sap_user', 50)->nullable();
            $table->string('invoice_no', 100)->nullable();
            $table->text('invoice_remarks')->nullable();
            $table->string('sync_batch', 40);
            $table->timestamp('synced_at');
            $table->enum('source', ['sap_auto', 'excel_manual'])->default('sap_auto');
            $table->timestamps();

            $table->unique(['trans_id', 'line_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ppn_input_sync');
    }
};
