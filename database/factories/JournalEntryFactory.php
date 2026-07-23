<?php

namespace Database\Factories;

use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class JournalEntryFactory extends Factory
{
    protected $model = JournalEntry::class;

    public function definition(): array
    {
        return [
            'number' => 'JE-'.str_pad((string) fake()->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
            'date' => fake()->date(),
            'memo' => fake()->sentence(),
            'reference' => fake()->optional()->bothify('REF-####'),
            'created_by' => User::factory(),
            'sap_submission_status' => 'pending',
            'sap_submission_attempts' => 0,
        ];
    }

    public function posted(): static
    {
        return $this->state(fn () => [
            'sap_journal_no' => 'SAP-'.fake()->numberBetween(1000, 9999),
            'sap_je_jdt_num' => (string) fake()->numberBetween(5000, 9999),
            'sap_posting_date' => now()->toDateString(),
            'sap_submission_status' => 'success',
            'sap_submission_attempts' => 1,
            'sap_submitted_at' => now(),
        ]);
    }
}
