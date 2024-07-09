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
        Schema::create('anggarans', function (Blueprint $table) {
            $table->id();
            $table->string('nomor')->nullable();
            $table->string('description')->nullable();
            $table->string('project', 20);
            $table->foreignId('department_id');
            $table->string('type', 20); // period / event
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->date('periode_budget')->nullable();
            $table->date('periode_ofr')->nullable();
            $table->decimal('amount', 20, 2)->default(0);
            $table->string('status', 20)->default('draft');
            $table->foreignId('created_by');
            $table->string('filename')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('anggarans');
    }
};
