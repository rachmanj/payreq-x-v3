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
            ['name' => 'Bank BPD Kaltim'],
            ['name' => 'Bank BCA'],
            ['name' => 'Bank BNI'],
            ['name' => 'Bank Panin'],
            ['name' => 'Bank Danamon'],
            ['name' => 'Bank Nusantara Parahyangan'],
            ['name' => 'Bank CIMB Niaga'],
            ['name' => 'Bank Syariah Indonesia'],
        ];

        DB::table('banks')->insert($banks);
    }
}
