<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_statement_lines', function (Blueprint $table) {
            $table->string('exclude_reason', 500)->nullable()->after('matched_status');
            $table->string('line_notes', 500)->nullable()->after('exclude_reason');
        });

        Schema::table('sap_gl_lines', function (Blueprint $table) {
            $table->string('exclude_reason', 500)->nullable()->after('matched_status');
            $table->string('line_notes', 500)->nullable()->after('exclude_reason');
        });
    }

    public function down(): void
    {
        Schema::table('bank_statement_lines', function (Blueprint $table) {
            $table->dropColumn(['exclude_reason', 'line_notes']);
        });

        Schema::table('sap_gl_lines', function (Blueprint $table) {
            $table->dropColumn(['exclude_reason', 'line_notes']);
        });
    }
};
