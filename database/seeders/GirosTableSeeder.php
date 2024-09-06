<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GirosTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $giros = [
            [
                'acc_no' => '1490004194751',
                'acc_name' => 'Bank Mandiri IDR 751',
                'bank_id' => 1,
                'type' => 'giro',
                'curr' => 'IDR',
                'project' => '000H',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'acc_no' => '1490000001158',
                'acc_name' => 'Bank Mandiri USD 58',
                'bank_id' => 1,
                'type' => 'giro',
                'curr' => 'USD',
                'project' => '000H',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Add more records as needed
        ];

        DB::table('giros')->insert($giros);
    }
}
