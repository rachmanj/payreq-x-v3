<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_entry_template_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_template_id')->constrained('journal_entry_templates')->cascadeOnDelete();
            $table->unsignedSmallInteger('line_no');
            $table->string('account_code', 50);
            $table->enum('debit_credit', ['debit', 'credit']);
            $table->decimal('default_amount', 15, 2)->nullable();
            $table->string('project', 20)->nullable();
            $table->string('cost_center', 50)->nullable();
            $table->string('description')->nullable();
            $table->timestamps();

            $table->index(['journal_entry_template_id', 'line_no'], 'je_tpl_lines_tpl_id_line_no_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entry_template_lines');
    }
};
