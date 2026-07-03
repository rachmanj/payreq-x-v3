<?php

namespace Database\Factories;

use App\Models\Meeting;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Meeting>
 */
class MeetingFactory extends Factory
{
    protected $model = Meeting::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'meeting_date' => fake()->date(),
            'original_filename' => fake()->slug().'.pdf',
            'file_path' => fake()->uuid().'.pdf',
            'status' => Meeting::STATUS_PROCESSED,
            'full_text' => fake()->paragraphs(3, true),
            'uploaded_by' => User::factory(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'status' => Meeting::STATUS_PENDING,
            'full_text' => null,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'status' => Meeting::STATUS_FAILED,
        ]);
    }
}
