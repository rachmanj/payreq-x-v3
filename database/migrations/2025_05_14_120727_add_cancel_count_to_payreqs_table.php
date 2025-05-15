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
            $table->integer('cancel_count')->default(0)->after('canceled_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payreqs', function (Blueprint $table) {
            $table->dropColumn('cancel_count');
        });
    }
};
