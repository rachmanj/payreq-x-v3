<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('parameters')
            ->where('name1', 'max_submitted_payreq')
            ->exists();

        if ($exists) {
            return;
        }

        DB::table('parameters')->insert([
            'name1' => 'max_submitted_payreq',
            'name2' => 'ALL',
            'param_value' => '5',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('parameters')
            ->where('name1', 'max_submitted_payreq')
            ->where('name2', 'ALL')
            ->where('param_value', '5')
            ->delete();
    }
};
