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
        Schema::create('dokumens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('giro_id')->nullable();
            $table->string('type', 30); // koran / pcbc / rekon
            $table->string('project', 20)->nullable();
            $table->date('periode')->nullable(); // Januari 2024 / Juni 2024
            $table->date('dokumen_date')->nullable(); // Tanggal dokumen, misal PCBC berarti tanggal cash opname
            $table->string('filename1');
            $table->string('filename2')->nullable();
            $table->string('remarks')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('verified_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dokumens');
    }
};
