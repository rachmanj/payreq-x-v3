<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payreq_anggaran_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payreq_id')->constrained('payreqs')->cascadeOnDelete();
            $table->foreignId('anggaran_id')->constrained('anggarans')->cascadeOnDelete();
            $table->decimal('amount', 20, 2);
            $table->string('remarks')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['payreq_id']);
            $table->index(['anggaran_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payreq_anggaran_allocations');
    }
};
