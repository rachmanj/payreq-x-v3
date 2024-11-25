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
        Schema::table('daily_txes', function (Blueprint $table) {
            $table->string('vendor_code', 50)->nullable();
            $table->string('vendor_name', 150)->nullable();
            $table->string('faktur_no', 100)->nullable();
            $table->date('faktur_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_txes', function (Blueprint $table) {
            $table->dropColumn('vendor_code');
            $table->dropColumn('vendor_name');
            $table->dropColumn('faktur_no');
            $table->dropColumn('faktur_date');
        });
    }
};
