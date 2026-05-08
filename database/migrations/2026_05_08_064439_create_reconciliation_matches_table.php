<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reconciliation_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_reconciliation_id')->constrained('bank_reconciliations')->cascadeOnDelete();
            $table->foreignId('bank_statement_line_id')->nullable()->constrained('bank_statement_lines')->cascadeOnDelete();
            $table->foreignId('sap_gl_line_id')->nullable()->constrained('sap_gl_lines')->cascadeOnDelete();
            $table->string('match_type', 32);
            $table->float('confidence_score')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('bank_reconciliation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reconciliation_matches');
    }
};
