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
        Schema::table('payreqs', function (Blueprint $table) {
            $table->string('lot_no')->nullable()->after('rab_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payreqs', function (Blueprint $table) {
            $table->dropColumn('lot_no');
        });
    }
};
