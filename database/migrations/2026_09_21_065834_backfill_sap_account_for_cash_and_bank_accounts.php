<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::table('accounts')
                ->whereIn('type', ['cash', 'bank'])
                ->where(function ($query) {
                    $query->whereNull('sap_account')
                        ->orWhere('sap_account', '');
                })
                ->whereRaw("account_number REGEXP '^[0-9]+$'")
                ->update(['sap_account' => DB::raw('account_number')]);

            return;
        }

        DB::table('accounts')
            ->whereIn('type', ['cash', 'bank'])
            ->where(function ($query) {
                $query->whereNull('sap_account')
                    ->orWhere('sap_account', '');
            })
            ->get(['id', 'account_number'])
            ->filter(fn ($row) => preg_match('/^[0-9]+$/', (string) $row->account_number) === 1)
            ->each(function ($row): void {
                DB::table('accounts')
                    ->where('id', $row->id)
                    ->update(['sap_account' => $row->account_number]);
            });
    }

    public function down(): void
    {
        DB::table('accounts')
            ->whereIn('type', ['cash', 'bank'])
            ->whereColumn('sap_account', 'account_number')
            ->update(['sap_account' => null]);
    }
};
