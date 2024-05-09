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
        Schema::create('payreq_migrasis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by');
            $table->foreignId('payreq_id');
            $table->string('old_payreq_no')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payreq_migrasis');
    }
};
