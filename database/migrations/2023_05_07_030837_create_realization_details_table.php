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
        Schema::create('realization_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('realization_id');
            $table->string('unit_no', 20)->nullable();
            $table->foreignId('account_id')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('realization_details');
    }
};
