<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('general_outgoing_payments', function (Blueprint $table) {
            $table->string('profit_center', 20)->nullable()->after('project');
        });
    }

    public function down(): void
    {
        Schema::table('general_outgoing_payments', function (Blueprint $table) {
            $table->dropColumn('profit_center');
        });
    }
};
