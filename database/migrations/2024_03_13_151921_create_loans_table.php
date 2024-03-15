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
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->string('loan_code')->nullable();
            $table->foreignId('creditor_id')->nullable();
            $table->date('start_date')->nullable();
            $table->integer('tenor')->default(0); // tenor in months
            $table->text('description')->nullable();
            $table->double('principal')->nullable();
            $table->string('status', 20)->nullable(); // paid-off, ongoing
            $table->foreignId('user_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
