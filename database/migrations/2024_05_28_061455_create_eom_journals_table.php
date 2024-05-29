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
        Schema::create('eom_journals', function (Blueprint $table) {
            $table->id();
            $table->string('nomor');
            $table->date('date');
            $table->string('type', 50)->nullable();
            $table->foreignId('created_by');
            $table->string('project', 20)->nullable();
            $table->string('sap_journal_no')->nullable();
            $table->date('sap_posting_date')->nullable();
            $table->foreignId('posted_by')->nullable(); // posted to SAP by
            $table->double('amount')->nullable();
            $table->string('description')->nullable();
            $table->string('status', 50)->nullable();
            $table->string('flag', 50)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('eom_journals');
    }
};
