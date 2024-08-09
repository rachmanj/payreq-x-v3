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
            $table->string('bank', 50)->nullable();
            $table->string('giro_type', 50)->nullable(); // tabungan, giro, deposito
            $table->string('account_no', 50)->nullable();
            $table->string('account_name', 50)->nullable();
            $table->string('currency', 5)->nullable();
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
