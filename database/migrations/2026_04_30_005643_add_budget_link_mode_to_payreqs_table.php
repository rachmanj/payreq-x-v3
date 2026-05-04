<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payreqs', function (Blueprint $table) {
            $table->string('budget_link_mode', 32)->nullable()->after('rab_id');
        });
    }

    public function down(): void
    {
        Schema::table('payreqs', function (Blueprint $table) {
            $table->dropColumn('budget_link_mode');
        });
    }
};
