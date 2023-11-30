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
        Schema::create('rabs', function (Blueprint $table) {
            $table->id();
            $table->string('rab_no', 50)->nullable();
            $table->date('date')->nullable();
            $table->string('description');
            $table->string('project_code', 10)->nullable();
            $table->integer('department_id')->nullable();
            $table->double('budget')->nullable();
            $table->string('status', 20)->nullable();
            $table->string('filename')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rabs');
    }
};
