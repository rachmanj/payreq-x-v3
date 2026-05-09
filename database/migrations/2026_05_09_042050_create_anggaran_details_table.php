<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anggaran_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('anggaran_id')->constrained('anggarans')->cascadeOnDelete();
            $table->foreignId('account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->string('description')->nullable();
            $table->decimal('qty', 18, 4)->default(1);
            $table->string('unit', 50)->nullable();
            $table->decimal('unit_price', 20, 2)->default(0);
            $table->decimal('amount', 20, 2)->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['anggaran_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anggaran_details');
    }
};
