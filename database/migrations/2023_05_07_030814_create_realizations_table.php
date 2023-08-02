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
        Schema::create('realizations', function (Blueprint $table) {
            $table->id();
            $table->string('number');
            $table->foreignId('payreq_id');
            $table->foreignId('user_id');
            $table->string('project', 20)->nullable();
            $table->string('remarks')->nullable();
            $table->string('status', 20)->nullable(); // draft / approved / rejected / paid / realized / verified
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('realizations');
    }
};
