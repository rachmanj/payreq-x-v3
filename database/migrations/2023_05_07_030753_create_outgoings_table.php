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
        Schema::create('outgoings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cashier_id')->nullable();
            $table->foreignId('payreq_id')->nullable();
            $table->foreignId('account_id')->nullable();
            $table->date('outgoing_date')->nullable();
            $table->double('amount')->nullable();
            $table->string('description')->nullable();
            $table->string('project', 10)->nullable();
            $table->foreignId('cash_journal_id')->nullable();
            $table->string('sap_journal_no', 50)->nullable();
            $table->string('flag', 10)->nullable(); // Cash Journal Temporary (CJT) + user_id
            $table->boolean('will_post')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('outgoings');
    }
};
