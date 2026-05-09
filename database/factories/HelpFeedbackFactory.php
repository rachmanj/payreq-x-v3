<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\HelpFeedback>
 */
class HelpFeedbackFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => fake()->randomElement(['bug', 'feature']),
            'title' => fake()->sentence(4),
            'body' => fake()->paragraph(),
            'steps_to_reproduce' => null,
        ];
    }
}
