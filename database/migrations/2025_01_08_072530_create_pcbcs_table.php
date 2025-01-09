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
        Schema::create('pcbcs', function (Blueprint $table) {
            $table->id();
            $table->string('nomor')->nullable();
            $table->date('pcbc_date');
            $table->foreignId('created_by')->constrained('users');
            $table->string('project');
            $table->integer('kertas_100rb')->nullable();
            $table->integer('kertas_50rb')->nullable();
            $table->integer('kertas_20rb')->nullable();
            $table->integer('kertas_10rb')->nullable();
            $table->integer('kertas_5rb')->nullable();
            $table->integer('kertas_2rb')->nullable();
            $table->integer('kertas_1rb')->nullable();
            $table->integer('kertas_500')->nullable();
            $table->integer('kertas_100')->nullable();
            $table->integer('logam_1rb')->nullable();
            $table->integer('logam_500')->nullable();
            $table->integer('logam_200')->nullable();
            $table->integer('logam_100')->nullable();
            $table->integer('logam_50')->nullable();
            $table->integer('logam_25')->nullable();
            $table->decimal('system_amount', 10, 2)->nullable();
            $table->decimal('fisik_amount', 10, 2)->nullable();
            $table->decimal('sap_amount', 10, 2)->nullable();
            $table->string('pemeriksa1');
            $table->string('pemeriksa2')->nullable();
            $table->string('approved_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pcbcs');
    }
};
