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
        Schema::create('fakturs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id');
            $table->string('invoice_no');
            $table->date('invoice_date');
            $table->string('faktur_no')->nullable();
            $table->date('faktur_date')->nullable();
            $table->decimal('kurs', 6, 2)->nullable();
            $table->decimal('dpp', 20, 2);
            $table->decimal('ppn', 20, 2)->nullable();
            $table->text('remarks')->nullable();
            $table->string('attachment')->nullable();
            $table->foreignId('created_by');
            $table->timestamp('submit_at')->nullable();
            $table->foreignId('response_by')->nullable();
            $table->timestamp('response_at')->nullable();
            $table->string('status', 20)->nullable(); // draft / submitted / responded / canceled / close
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fakturs');
    }
};
