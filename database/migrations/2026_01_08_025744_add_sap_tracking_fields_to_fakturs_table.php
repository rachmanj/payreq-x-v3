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
        Schema::table('fakturs', function (Blueprint $table) {
            // Revenue account selection
            if (!Schema::hasColumn('fakturs', 'revenue_account_code')) {
                $table->string('revenue_account_code', 20)->nullable()->after('account');
            }

            // SAP AR Invoice tracking
            if (!Schema::hasColumn('fakturs', 'sap_ar_doc_num')) {
                $table->string('sap_ar_doc_num')->nullable()->after('doc_num');
            }
            if (!Schema::hasColumn('fakturs', 'sap_ar_doc_entry')) {
                $table->string('sap_ar_doc_entry')->nullable()->after('sap_ar_doc_num');
            }

            // SAP Journal Entry tracking
            if (!Schema::hasColumn('fakturs', 'sap_je_num')) {
                $table->string('sap_je_num')->nullable()->after('sap_ar_doc_entry');
            }
            if (!Schema::hasColumn('fakturs', 'sap_je_doc_entry')) {
                $table->string('sap_je_doc_entry')->nullable()->after('sap_je_num');
            }

            // Submission tracking
            if (!Schema::hasColumn('fakturs', 'sap_submission_status')) {
                $table->enum('sap_submission_status', ['pending', 'ar_created', 'je_created', 'completed', 'failed'])
                    ->nullable()->after('status');
            }
            if (!Schema::hasColumn('fakturs', 'sap_submission_attempts')) {
                $table->integer('sap_submission_attempts')->default(0)->after('sap_submission_status');
            }
            if (!Schema::hasColumn('fakturs', 'sap_submission_error')) {
                $table->text('sap_submission_error')->nullable()->after('sap_submission_attempts');
            }
            if (!Schema::hasColumn('fakturs', 'sap_submitted_at')) {
                $table->timestamp('sap_submitted_at')->nullable()->after('sap_submission_error');
            }
            if (!Schema::hasColumn('fakturs', 'sap_submitted_by')) {
                $table->foreignId('sap_submitted_by')->nullable()->after('sap_submitted_at');
            }

            // Project for JE (Department comes from customer default)
            if (!Schema::hasColumn('fakturs', 'project')) {
                $table->string('project', 10)->nullable()->after('customer_id');
            }

            // WTax field (if not exists)
            if (!Schema::hasColumn('fakturs', 'wtax_amount')) {
                $table->decimal('wtax_amount', 20, 2)->nullable()->after('ppn');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fakturs', function (Blueprint $table) {
            $columns = [
                'revenue_account_code',
                'sap_ar_doc_num',
                'sap_ar_doc_entry',
                'sap_je_num',
                'sap_je_doc_entry',
                'sap_submission_status',
                'sap_submission_attempts',
                'sap_submission_error',
                'sap_submitted_at',
                'project',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('fakturs', $column)) {
                    $table->dropColumn($column);
                }
            }

            if (Schema::hasColumn('fakturs', 'sap_submitted_by')) {
                $table->dropForeign(['sap_submitted_by']);
                $table->dropColumn('sap_submitted_by');
            }

            if (Schema::hasColumn('fakturs', 'wtax_amount')) {
                $table->dropColumn('wtax_amount');
            }
        });
    }
};
