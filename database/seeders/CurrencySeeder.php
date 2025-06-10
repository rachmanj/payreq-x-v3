<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $currencies = [
            ['currency_code' => 'IDR', 'currency_name' => 'Indonesian Rupiah', 'symbol' => 'Rp', 'created_by' => 1],
            ['currency_code' => 'USD', 'currency_name' => 'US Dollar', 'symbol' => '$', 'created_by' => 1],
            ['currency_code' => 'EUR', 'currency_name' => 'Euro', 'symbol' => '€', 'created_by' => 1],
            ['currency_code' => 'SGD', 'currency_name' => 'Singapore Dollar', 'symbol' => 'S$', 'created_by' => 1],
            ['currency_code' => 'JPY', 'currency_name' => 'Japanese Yen', 'symbol' => '¥', 'created_by' => 1],
            ['currency_code' => 'GBP', 'currency_name' => 'British Pound', 'symbol' => '£', 'created_by' => 1],
            ['currency_code' => 'AUD', 'currency_name' => 'Australian Dollar', 'symbol' => 'A$', 'created_by' => 1],
            ['currency_code' => 'CNY', 'currency_name' => 'Chinese Yuan', 'symbol' => '¥', 'created_by' => 1],
            ['currency_code' => 'KRW', 'currency_name' => 'South Korean Won', 'symbol' => '₩', 'created_by' => 1],
            ['currency_code' => 'MYR', 'currency_name' => 'Malaysian Ringgit', 'symbol' => 'RM', 'created_by' => 1],
        ];

        foreach ($currencies as $currency) {
            DB::table('currencies')->insert(array_merge($currency, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
