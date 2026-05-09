<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('help_embeddings', function (Blueprint $table) {
            $table->id();
            $table->string('chunk_key', 64)->unique();
            $table->string('source_path', 2048);
            $table->string('heading')->nullable();
            $table->string('locale', 16)->default('both');
            $table->longText('content');
            $table->json('embedding');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('help_embeddings');
    }
};
