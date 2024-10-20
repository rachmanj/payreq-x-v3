<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CustomerTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customers = [
            [
                'name' => 'Kayan Putra Utama Coal, PT',
                'project' => '017C',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Solusi Bangun Indonesia, PT',
                'project' => '021C',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Graha Panca Karsa, PT',
                'project' => '022C',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Tambang Raya Usaha Tama, PT',
                'project' => '023C',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ];

        DB::table('customers')->insert($customers);
    }
}
