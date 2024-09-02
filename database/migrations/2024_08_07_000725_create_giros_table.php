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
        Schema::create('giros', function (Blueprint $table) {
            $table->id();
            $table->string('acc_no', 50)->nullable();
            $table->string('acc_name', 50)->nullable();
            $table->foreignId('bank_id');
            $table->string('type', 50)->nullable(); // tabungan, giro, deposito
            $table->string('curr', 5)->default('idr');
            $table->string('project', 50)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('giros');
    }
};
