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
        Schema::table('verification_journals', function (Blueprint $table) {
            $table->integer('sap_submission_attempts')->default(0)->after('sap_posting_date');
            $table->enum('sap_submission_status', ['pending', 'success', 'failed'])->nullable()->after('sap_submission_attempts');
            $table->text('sap_submission_error')->nullable()->after('sap_submission_status');
            $table->timestamp('sap_submitted_at')->nullable()->after('sap_submission_error');
            $table->foreignId('sap_submitted_by')->nullable()->after('sap_submitted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('verification_journals', function (Blueprint $table) {
            $table->dropColumn([
                'sap_submission_attempts',
                'sap_submission_status',
                'sap_submission_error',
                'sap_submitted_at',
                'sap_submitted_by',
            ]);
        });
    }
};
