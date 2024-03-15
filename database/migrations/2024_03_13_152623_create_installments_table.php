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
        Schema::create('installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained('loans')->cascadeOnDelete();
            $table->date('due_date')->nullable();
            $table->string('bilyet_no')->nullable();
            $table->double('bilyet_amount')->nullable();
            $table->date('paid_date')->nullable();
            $table->integer('angsuran_ke')->nullable();
            $table->foreignId('account_id')->nullable();
            $table->string('status', 20)->nullable(); // paid
            $table->foreignId('created_by')->nullable();
            $table->integer('batch')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('installments');
    }
};
