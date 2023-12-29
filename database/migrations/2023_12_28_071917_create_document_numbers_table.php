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
        Schema::create('document_numbers', function (Blueprint $table) {
            $table->id();
            $table->string('document_type'); // Payreq, Realization, PCBC, 
            $table->integer('last_number')->default(0); // 0001
            $table->string('project')->nullable(); // 0001
            $table->string('year')->nullable(); // 2021
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_numbers');
    }
};
