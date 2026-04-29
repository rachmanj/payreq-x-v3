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
        Schema::create('realization_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('realization_id')->constrained()->cascadeOnDelete();
            $table->string('original_name');
            $table->string('stored_path');
            $table->string('mime', 127)->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('realization_attachments');
    }
};
