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
        Schema::create('approval_plans', function (Blueprint $table) {
            $table->id();
            $table->integer('document_id');
            $table->string('document_type'); // payreq | realization | rab
            $table->foreignId('approver_id');
            $table->integer('status')->default(0); // pending = 0 | approved = 1 | revised = 2 | rejected = 3  | cancelled = 4
            $table->string('remarks')->nullable();
            $table->boolean('is_open')->default(true); // approval yg disetujui dihitung hanya yg is_open = true
            $table->boolean('is_read')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_plans');
    }
};
