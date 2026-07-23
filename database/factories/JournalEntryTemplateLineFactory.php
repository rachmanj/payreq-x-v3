<?php

namespace Database\Factories;

use App\Models\JournalEntryTemplate;
use App\Models\JournalEntryTemplateLine;
use Illuminate\Database\Eloquent\Factories\Factory;

class JournalEntryTemplateLineFactory extends Factory
{
    protected $model = JournalEntryTemplateLine::class;

    public function definition(): array
    {
        return [
            'journal_entry_template_id' => JournalEntryTemplate::factory(),
            'line_no' => 1,
            'account_code' => fake()->numerify('#####'),
            'debit_credit' => 'debit',
            'default_amount' => null,
            'project' => '000H',
            'cost_center' => 'FIN',
            'description' => fake()->sentence(),
        ];
    }
}
