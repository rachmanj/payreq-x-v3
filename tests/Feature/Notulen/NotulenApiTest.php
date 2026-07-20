<?php

namespace Tests\Feature\Notulen;

use App\Models\ApiKey;
use App\Models\Meeting;
use App\Models\MeetingChunk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NotulenApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    }

    protected function stubOpenRouter(): void
    {
        config([
            'services.openrouter.api_key' => 'test-key',
            'services.openrouter.base_url' => 'https://openrouter.ai/api/v1',
            'services.openrouter.embedding_model' => 'mock/model',
            'services.openrouter.notulen_model' => 'mock/chat',
        ]);

        Http::fake([
            'https://openrouter.ai/api/v1/embeddings*' => Http::response([
                'data' => [['embedding' => [1.0, 0.0, 0.0, 0.0], 'index' => 0]],
            ], 200),
            'https://openrouter.ai/api/v1/chat/completions*' => Http::response([
                'choices' => [['message' => ['content' => 'Jawaban dari notulen.']]],
            ], 200),
        ]);
    }

    protected function createApiKey(): string
    {
        $user = User::factory()->create();
        $result = ApiKey::generate('Test Notulen Key', 'notulen-test', null, $user->id);

        return $result['raw_key'];
    }

    public function test_ask_requires_api_key(): void
    {
        $this->postJson('/api/notulen/ask', [
            'question' => 'Test?',
        ])->assertUnauthorized();
    }

    public function test_ask_returns_answer_with_valid_api_key(): void
    {
        $meeting = Meeting::factory()->create([
            'title' => 'Rapat API',
            'meeting_date' => '2026-03-01',
        ]);

        MeetingChunk::factory()->create([
            'meeting_id' => $meeting->id,
            'embedding' => [1.0, 0.0, 0.0, 0.0],
            'content' => 'Konten rapat untuk API test.',
        ]);

        $this->stubOpenRouter();
        $rawKey = $this->createApiKey();

        $this->postJson('/api/notulen/ask', [
            'question' => 'Apa isi rapat?',
        ], [
            'X-API-Key' => $rawKey,
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('not_found', false)
            ->assertJsonFragment(['title' => 'Rapat API'])
            ->assertJsonStructure([
                'success',
                'answer',
                'sources' => [
                    ['id', 'title', 'meeting_date', 'url', 'excerpt', 'score', 'chunk_index'],
                ],
                'not_found',
                'top_score',
                'model',
                'latency_ms',
            ]);
    }

    public function test_meetings_list_requires_api_key(): void
    {
        $this->getJson('/api/notulen/meetings')->assertUnauthorized();
    }

    public function test_meetings_list_returns_indexed_meetings(): void
    {
        Meeting::factory()->count(2)->create();

        $rawKey = $this->createApiKey();

        $this->getJson('/api/notulen/meetings', [
            'X-API-Key' => $rawKey,
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data');
    }
}
