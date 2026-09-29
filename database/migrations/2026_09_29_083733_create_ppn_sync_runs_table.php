<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ppn_sync_runs', function (Blueprint $table) {
            $table->id();
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->enum('status', ['running', 'success', 'failed']);
            $table->unsignedInteger('rows_fetched')->default(0);
            $table->unsignedInteger('rows_upserted')->default(0);
            $table->unsignedInteger('rows_faktur_created')->default(0);
            $table->text('message')->nullable();
            $table->unsignedBigInteger('triggered_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ppn_sync_runs');
    }
};
