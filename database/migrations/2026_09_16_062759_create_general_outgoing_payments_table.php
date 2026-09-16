<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('general_outgoing_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('giro_id')->constrained('giros');
            $table->foreignId('bilyet_id')->constrained('bilyets');
            $table->date('posting_date');
            $table->date('doc_date');
            $table->unsignedBigInteger('amount');
            $table->string('remarks')->nullable();
            $table->string('project', 10);
            $table->string('akun_tujuan_utama', 50);
            $table->string('sap_doc_num', 50)->nullable();
            $table->unsignedBigInteger('sap_doc_entry')->nullable();
            $table->string('status', 30)->default('success');
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamp('submitted_at')->nullable();
            $table->text('sap_error_message')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('bilyet_id');
            $table->index('sap_doc_num');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('general_outgoing_payments');
    }
};
