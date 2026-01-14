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
        Schema::table('sap_submission_logs', function (Blueprint $table) {
            // Add faktur_id and document_type to support both verification journals and fakturs
            if (!Schema::hasColumn('sap_submission_logs', 'faktur_id')) {
                $table->foreignId('faktur_id')->nullable()->after('verification_journal_id');
            }
            if (!Schema::hasColumn('sap_submission_logs', 'document_type')) {
                $table->string('document_type', 50)->nullable()->after('faktur_id'); // 'journal_entry', 'ar_invoice', 'journal_entry'
            }
            if (!Schema::hasColumn('sap_submission_logs', 'sap_doc_num')) {
                $table->string('sap_doc_num')->nullable()->after('sap_journal_number');
            }
            if (!Schema::hasColumn('sap_submission_logs', 'sap_doc_entry')) {
                $table->string('sap_doc_entry')->nullable()->after('sap_doc_num');
            }
            if (!Schema::hasColumn('sap_submission_logs', 'sap_error')) {
                $table->text('sap_error')->nullable()->after('error_message');
            }
            if (!Schema::hasColumn('sap_submission_logs', 'submitted_by')) {
                $table->foreignId('submitted_by')->nullable()->after('user_id');
            }
        });

        // Add foreign key constraint if faktur_id column was added
        if (Schema::hasColumn('sap_submission_logs', 'faktur_id')) {
            Schema::table('sap_submission_logs', function (Blueprint $table) {
                $table->foreign('faktur_id')->references('id')->on('fakturs')->onDelete('cascade');
                $table->index('faktur_id');
            });
        }

        // Make verification_journal_id nullable since we now support fakturs
        Schema::table('sap_submission_logs', function (Blueprint $table) {
            $table->foreignId('verification_journal_id')->nullable()->change();
            $table->foreignId('user_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sap_submission_logs', function (Blueprint $table) {
            if (Schema::hasColumn('sap_submission_logs', 'faktur_id')) {
                $table->dropForeign(['faktur_id']);
                $table->dropColumn('faktur_id');
            }
            if (Schema::hasColumn('sap_submission_logs', 'document_type')) {
                $table->dropColumn('document_type');
            }
            if (Schema::hasColumn('sap_submission_logs', 'sap_doc_num')) {
                $table->dropColumn('sap_doc_num');
            }
            if (Schema::hasColumn('sap_submission_logs', 'sap_doc_entry')) {
                $table->dropColumn('sap_doc_entry');
            }
            if (Schema::hasColumn('sap_submission_logs', 'sap_error')) {
                $table->dropColumn('sap_error');
            }
            if (Schema::hasColumn('sap_submission_logs', 'submitted_by')) {
                $table->dropForeign(['submitted_by']);
                $table->dropColumn('submitted_by');
            }
        });

        // Revert nullable changes
        Schema::table('sap_submission_logs', function (Blueprint $table) {
            $table->foreignId('verification_journal_id')->nullable(false)->change();
            $table->foreignId('user_id')->nullable(false)->change();
        });
    }
};
