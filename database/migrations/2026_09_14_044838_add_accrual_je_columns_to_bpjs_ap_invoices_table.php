<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bpjs_ap_invoices', function (Blueprint $table) {
            $table->boolean('auto_je')->default(true)->after('submitted_at');
            $table->date('je_posting_date')->nullable()->after('auto_je');
            $table->foreignId('journal_entry_id')->nullable()->after('je_posting_date')
                ->constrained('journal_entries')->nullOnDelete();
            $table->enum('je_status', ['pending', 'success', 'failed', 'skipped'])->nullable()->after('journal_entry_id');
            $table->text('je_error')->nullable()->after('je_status');
            $table->timestamp('je_submitted_at')->nullable()->after('je_error');
            $table->unsignedBigInteger('je_submitted_by')->nullable()->after('je_submitted_at');

            $table->foreign('je_submitted_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('bpjs_ap_invoices', function (Blueprint $table) {
            $table->dropForeign(['journal_entry_id']);
            $table->dropForeign(['je_submitted_by']);
            $table->dropColumn([
                'auto_je',
                'je_posting_date',
                'journal_entry_id',
                'je_status',
                'je_error',
                'je_submitted_at',
                'je_submitted_by',
            ]);
        });
    }
};
