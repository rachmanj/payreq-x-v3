<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\HelpEmbedding>
 */
class HelpEmbeddingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'chunk_key' => hash('sha256', fake()->uuid().fake()->word()),
            'source_path' => 'docs/manuals/'.fake()->slug().'-en.md',
            'heading' => fake()->sentence(3),
            'locale' => 'en',
            'content' => fake()->paragraph(),
            'embedding' => [1.0, 0.0, 0.0, 0.0],
        ];
    }
}
