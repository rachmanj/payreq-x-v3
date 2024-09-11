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
        Schema::create('bilyet_temps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('giro_id')->nullable();
            $table->string('acc_no', 50)->nullable();
            $table->string('prefix', 10)->nullable();
            $table->string('nomor', 30);
            $table->string('type', 20)->nullable(); //cek or bg or loa
            $table->date('bilyet_date')->nullable();
            $table->date('cair_date')->nullable();
            $table->decimal('amount', 20, 2)->nullable();
            $table->string('remarks')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->string('project', 10)->nullable();
            $table->string('status', 30)->default('onhand'); //onhand / release / cair / void
            $table->integer('batch')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bilyet_temps');
    }
};
