<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BanksTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $banks = [
            ['name' => 'Bank Mandiri'],
            ['name' => 'Bank BCA'],
            ['name' => 'Bank BNI'],
            ['name' => 'Bank BRI'],
            ['name' => 'Bank Danamon'],
            ['name' => 'Bank Permata'],
            ['name' => 'Bank Syariah Indonesia'],
            ['name' => 'Bank CIMB Niaga'],
            ['name' => 'Bank BTN'],
        ];

        DB::table('banks')->insert($banks);
    }
}
