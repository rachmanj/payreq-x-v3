<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('parameters')
            ->where('name1', 'petty_cash_account')
            ->where('name2', '022C')
            ->exists();

        if ($exists) {
            return;
        }

        DB::table('parameters')->insert([
            'name1' => 'petty_cash_account',
            'name2' => '022C',
            'param_value' => '11101010',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('parameters')
            ->where('name1', 'petty_cash_account')
            ->where('name2', '022C')
            ->where('param_value', '11101010')
            ->delete();
    }
};
