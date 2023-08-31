<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cash_journals', function (Blueprint $table) {
            $table->id();
            $table->string('journal_no')->nullable();
            $table->string('type');
            $table->string('description');
            $table->date('date');
            $table->string('sap_journal_no', 50)->nullable();
            $table->date('sap_posting_date')->nullable();
            $table->double('amount')->nullable();
            $table->string('project', 20)->default('000H')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_journals');
    }
};
