<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->string('number', 50)->unique();
            $table->date('date');
            $table->text('memo')->nullable();
            $table->string('reference')->nullable();
            $table->foreignId('journal_entry_template_id')->nullable()->constrained('journal_entry_templates')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->string('sap_journal_no')->nullable();
            $table->string('sap_je_jdt_num')->nullable();
            $table->date('sap_posting_date')->nullable();
            $table->enum('sap_submission_status', ['pending', 'success', 'failed'])->default('pending');
            $table->unsignedInteger('sap_submission_attempts')->default(0);
            $table->text('sap_submission_error')->nullable();
            $table->timestamp('sap_submitted_at')->nullable();
            $table->foreignId('sap_submitted_by')->nullable()->constrained('users');
            $table->timestamp('sap_reversed_at')->nullable();
            $table->foreignId('sap_reversed_by')->nullable()->constrained('users');
            $table->text('sap_reversal_reason')->nullable();
            $table->string('sap_reversal_journal_no')->nullable();
            $table->timestamps();

            $table->index('sap_submission_status');
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
    }
};
