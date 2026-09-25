<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $parameters = [
            [
                'name1' => 'cashier_vj_sap_limit',
                'name2' => 'ALL',
                'param_value' => '100000000',
            ],
            [
                'name1' => 'cashier_vj_sap_accounts',
                'name2' => 'ALL',
                'param_value' => '11101005,11101008,11101010,11101004,11101006,71201001,71201006,71201007,71201002,71101001',
            ],
        ];

        foreach ($parameters as $parameter) {
            $exists = DB::table('parameters')
                ->where('name1', $parameter['name1'])
                ->where('name2', $parameter['name2'])
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('parameters')->insert(array_merge($parameter, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    public function down(): void
    {
        DB::table('parameters')
            ->where('name1', 'cashier_vj_sap_limit')
            ->where('name2', 'ALL')
            ->where('param_value', '100000000')
            ->delete();

        DB::table('parameters')
            ->where('name1', 'cashier_vj_sap_accounts')
            ->where('name2', 'ALL')
            ->delete();
    }
};
