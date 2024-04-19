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
        Schema::table('verification_journals', function (Blueprint $table) {
            $table->foreignId('posted_by')->nullable(); // posted to SAP by
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('verification_journals', function (Blueprint $table) {
            //
        });
    }
};
