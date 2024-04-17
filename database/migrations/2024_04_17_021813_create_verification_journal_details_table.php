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
        Schema::create('verification_journal_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('verification_journal_id');
            $table->date('realization_date');
            $table->string('account_code');
            $table->string('debit_credit'); // debit or credit
            $table->string('description');
            $table->string('sap_journal_no')->nullable();
            $table->string('realization_no')->nullable();
            $table->string('project');
            $table->string('cost_center'); // dept akronim
            $table->decimal('amount', 15, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('verification_journal_details');
    }
};
