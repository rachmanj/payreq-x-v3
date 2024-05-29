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
        Schema::create('eom_journal_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('eom_journal_id')->constrained()->onDelete('cascade');
            $table->date('posting_date');
            $table->string('account_number');
            $table->string('d_c', 10); // debit or credit
            $table->string('description');
            $table->string('project');
            $table->string('cost_center'); // dept code
            $table->decimal('amount', 15, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('eom_journal_details');
    }
};
