<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_statement_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_reconciliation_id')->constrained('bank_reconciliations')->cascadeOnDelete();
            $table->date('transaction_date')->nullable();
            $table->date('value_date')->nullable();
            $table->text('description')->nullable();
            $table->string('reference', 191)->nullable();
            $table->decimal('debit', 18, 2)->default(0);
            $table->decimal('credit', 18, 2)->default(0);
            $table->decimal('balance', 18, 2)->nullable();
            $table->boolean('is_ai_extracted')->default(true);
            $table->float('ai_confidence')->nullable();
            $table->string('matched_status', 32)->default('unmatched');
            $table->unsignedInteger('line_order')->nullable();
            $table->timestamps();

            $table->index(['bank_reconciliation_id', 'matched_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_statement_lines');
    }
};
