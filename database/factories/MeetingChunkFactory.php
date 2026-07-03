<?php

namespace Database\Factories;

use App\Models\Meeting;
use App\Models\MeetingChunk;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MeetingChunk>
 */
class MeetingChunkFactory extends Factory
{
    protected $model = MeetingChunk::class;

    public function definition(): array
    {
        return [
            'meeting_id' => Meeting::factory(),
            'chunk_index' => 0,
            'content' => fake()->paragraph(),
            'embedding' => [1.0, 0.0, 0.0, 0.0],
        ];
    }
}
