<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('parameters')
            ->where('name1', 'dashboard_clearing_accounts')
            ->where('name2', 'ALL')
            ->exists();

        if ($exists) {
            return;
        }

        DB::table('parameters')->insert([
            'name1' => 'dashboard_clearing_accounts',
            'name2' => 'ALL',
            'param_value' => '13101020,13101021',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('parameters')
            ->where('name1', 'dashboard_clearing_accounts')
            ->where('name2', 'ALL')
            ->where('param_value', '13101020,13101021')
            ->delete();
    }
};
