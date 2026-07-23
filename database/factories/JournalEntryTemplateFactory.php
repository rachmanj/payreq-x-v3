<?php

namespace Database\Factories;

use App\Models\JournalEntryTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class JournalEntryTemplateFactory extends Factory
{
    protected $model = JournalEntryTemplate::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'created_by' => User::factory(),
        ];
    }
}
