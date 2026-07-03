<?php

namespace Database\Factories;

use App\Models\NotulenQuestion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\NotulenQuestion>
 */
class NotulenQuestionFactory extends Factory
{
    protected $model = NotulenQuestion::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'question' => fake()->sentence().'?',
            'answer' => fake()->paragraph(),
            'sources' => [],
            'created_at' => now(),
        ];
    }
}
