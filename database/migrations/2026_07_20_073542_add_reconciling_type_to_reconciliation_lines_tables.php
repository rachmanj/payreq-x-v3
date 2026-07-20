<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_statement_lines', function (Blueprint $table) {
            $table->string('reconciling_type', 64)->nullable()->after('exclude_reason');
        });

        Schema::table('sap_gl_lines', function (Blueprint $table) {
            $table->string('reconciling_type', 64)->nullable()->after('exclude_reason');
        });
    }

    public function down(): void
    {
        Schema::table('bank_statement_lines', function (Blueprint $table) {
            $table->dropColumn('reconciling_type');
        });

        Schema::table('sap_gl_lines', function (Blueprint $table) {
            $table->dropColumn('reconciling_type');
        });
    }
};
