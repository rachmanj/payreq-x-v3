<?php

namespace Database\Factories;

use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Database\Eloquent\Factories\Factory;

class JournalEntryLineFactory extends Factory
{
    protected $model = JournalEntryLine::class;

    public function definition(): array
    {
        return [
            'journal_entry_id' => JournalEntry::factory(),
            'line_no' => 1,
            'account_code' => fake()->numerify('#####'),
            'debit_credit' => 'debit',
            'amount' => fake()->randomFloat(2, 100, 10000),
            'project' => '000H',
            'cost_center' => 'FIN',
            'description' => fake()->sentence(),
        ];
    }
}
