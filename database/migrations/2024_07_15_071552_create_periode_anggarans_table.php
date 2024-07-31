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
        Schema::create('periode_anggarans', function (Blueprint $table) {
            $table->id();
            $table->string('periode_type', 20); // anggaran, ofr
            $table->date('periode')->nullable();
            $table->string('description')->nullable();
            $table->string('project', 10)->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('periode_anggarans');
    }
};
