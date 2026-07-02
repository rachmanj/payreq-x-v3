<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('reconciliation_match_groups')->orderBy('id')->chunkById(100, function ($groups): void {
            foreach ($groups as $group) {
                $bankTotal = (float) $group->bank_total;
                $sapTotal = (float) $group->sap_total;

                DB::table('reconciliation_match_groups')
                    ->where('id', $group->id)
                    ->update([
                        'difference' => number_format($bankTotal + $sapTotal, 2, '.', ''),
                    ]);
            }
        });
    }

    public function down(): void
    {
        DB::table('reconciliation_match_groups')->orderBy('id')->chunkById(100, function ($groups): void {
            foreach ($groups as $group) {
                $bankTotal = (float) $group->bank_total;
                $sapTotal = (float) $group->sap_total;

                DB::table('reconciliation_match_groups')
                    ->where('id', $group->id)
                    ->update([
                        'difference' => number_format($bankTotal - $sapTotal, 2, '.', ''),
                    ]);
            }
        });
    }
};
