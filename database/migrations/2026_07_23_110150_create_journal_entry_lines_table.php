<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_entry_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained('journal_entries')->cascadeOnDelete();
            $table->unsignedSmallInteger('line_no');
            $table->string('account_code', 50);
            $table->enum('debit_credit', ['debit', 'credit']);
            $table->decimal('amount', 15, 2);
            $table->string('project', 20)->nullable();
            $table->string('cost_center', 50)->nullable();
            $table->string('description')->nullable();
            $table->timestamps();

            $table->index(['journal_entry_id', 'line_no'], 'je_lines_entry_id_line_no_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entry_lines');
    }
};
