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
        Schema::create('invoice_creations', function (Blueprint $table) {
            $table->id();
            $table->date('create_date');
            $table->date('posting_date');
            $table->integer('duration');
            $table->string('document_number');
            $table->string('user_code');
            $table->integer('batch_number');
            $table->foreignId('uploaded_by');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_creations');
    }
};
