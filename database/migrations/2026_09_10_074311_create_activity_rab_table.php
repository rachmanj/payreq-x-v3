<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_rab', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained('activities')->cascadeOnDelete();
            $table->foreignId('anggaran_id')->constrained('anggarans')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['activity_id', 'anggaran_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_rab');
    }
};
