<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('verification_journals')
            ->whereNotNull('sap_journal_no')
            ->where('validation_status', '!=', 'validated')
            ->update(['validation_status' => 'validated']);
    }

    public function down(): void
    {
        // Data-only backfill; cannot reliably restore prior validation_status values.
    }
};
