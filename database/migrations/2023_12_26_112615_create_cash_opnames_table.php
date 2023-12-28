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
        Schema::create('cash_opnames', function (Blueprint $table) {
            $table->id();
            $table->string('nomor', 100);
            $table->string('project', 50);
            $table->date('date');
            $table->foreignId('cashier_id');
            $table->string('checked_by', 100)->nullable(); // pemeriksa fisik kas
            $table->string('approved_by', 100)->nullable(); // name PM / manager / atasan
            $table->integer('seratus_ribu')->default(0);
            $table->integer('lima_puluh_ribu')->default(0);
            $table->integer('dua_puluh_ribu')->default(0);
            $table->integer('sepuluh_ribu')->default(0);
            $table->integer('lima_ribu')->default(0);
            $table->integer('dua_ribu')->default(0);
            $table->integer('seribu')->default(0);
            $table->integer('lima_ratus')->default(0);
            $table->integer('seratus')->default(0);
            $table->integer('coin_seribu')->default(0);
            $table->integer('coin_lima_ratus')->default(0);
            $table->integer('coin_dua_ratus')->default(0);
            $table->integer('coin_seratus')->default(0);
            $table->integer('coin_lima_puluh')->default(0);
            $table->integer('coin_dua_puluh_lima')->default(0);
            $table->double('fisik_total')->default(0);
            $table->double('sap_balance')->default(0); // SAP PC Migrasi Balance
            $table->double('app_balance')->default(0); // application payreq-x balance
            $table->text('remarks')->nullable();
            $table->string('filename')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_opnames');
    }
};
