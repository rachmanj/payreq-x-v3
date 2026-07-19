<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sap_submission_logs', function (Blueprint $table) {
            $table->enum('action', ['submission', 'reversal'])->default('submission')->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('sap_submission_logs', function (Blueprint $table) {
            $table->dropColumn('action');
        });
    }
};
