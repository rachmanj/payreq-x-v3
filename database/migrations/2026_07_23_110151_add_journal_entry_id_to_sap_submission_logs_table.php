<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sap_submission_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('sap_submission_logs', 'journal_entry_id')) {
                $table->foreignId('journal_entry_id')->nullable()->after('faktur_id');
            }
        });

        if (Schema::hasColumn('sap_submission_logs', 'journal_entry_id')) {
            Schema::table('sap_submission_logs', function (Blueprint $table) {
                $table->foreign('journal_entry_id')->references('id')->on('journal_entries')->onDelete('cascade');
                $table->index('journal_entry_id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('sap_submission_logs', function (Blueprint $table) {
            if (Schema::hasColumn('sap_submission_logs', 'journal_entry_id')) {
                $table->dropForeign(['journal_entry_id']);
                $table->dropColumn('journal_entry_id');
            }
        });
    }
};
