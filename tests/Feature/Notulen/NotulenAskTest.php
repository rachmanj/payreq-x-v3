<?php

namespace Tests\Feature\Notulen;

use App\Models\Meeting;
use App\Models\MeetingChunk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NotulenAskTest extends TestCase
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
                'choices' => [['message' => ['content' => 'Keputusan anggaran disetujui pada rapat tersebut.']]],
            ], 200),
        ]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->stubOpenRouter();

        $this->post(route('notulen.ask'), [
            'question' => 'test',
        ])->assertRedirect(route('login'));
    }

    public function test_forbidden_when_user_has_no_permission(): void
    {
        $user = User::factory()->create();
        $this->stubOpenRouter();

        $this->actingAs($user)
            ->postJson(route('notulen.ask'), [
                'question' => 'Apa isi rapat?',
            ])
            ->assertForbidden();
    }

    public function test_returns_not_found_when_no_chunks(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('akses_notulen');

        $this->stubOpenRouter();

        $this->actingAs($user)
            ->postJson(route('notulen.ask'), [
                'question' => 'Apa isi rapat?',
            ])
            ->assertOk()
            ->assertJsonPath('not_found', true)
            ->assertJsonFragment(['answer' => 'Saya tidak menemukan informasi terkait pertanyaan ini dalam notulen rapat yang telah diindeks.']);
    }

    public function test_returns_english_not_found_for_english_question(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('akses_notulen');

        $this->stubOpenRouter();

        $this->actingAs($user)
            ->postJson(route('notulen.ask'), [
                'question' => 'What was decided about the budget?',
            ])
            ->assertOk()
            ->assertJsonPath('not_found', true)
            ->assertJsonFragment(['answer' => 'I could not find relevant information for this question in the indexed meeting minutes.']);
    }

    public function test_returns_answer_when_similarity_above_threshold(): void
    {
        $meeting = Meeting::factory()->create([
            'title' => 'Rapat Anggaran Q1',
            'meeting_date' => '2026-01-15',
        ]);

        MeetingChunk::factory()->create([
            'meeting_id' => $meeting->id,
            'embedding' => [1.0, 0.0, 0.0, 0.0],
            'content' => 'Rapat membahas anggaran Q1 dan disetujui oleh peserta.',
        ]);

        $user = User::factory()->create();
        $user->givePermissionTo('akses_notulen');

        $this->stubOpenRouter();

        $response = $this->actingAs($user)
            ->postJson(route('notulen.ask'), [
                'question' => 'Apa keputusan anggaran?',
            ])
            ->assertOk()
            ->assertJsonPath('not_found', false)
            ->assertJsonFragment(['title' => 'Rapat Anggaran Q1'])
            ->assertJsonStructure([
                'answer',
                'sources' => [
                    ['id', 'title', 'meeting_date', 'url', 'excerpt', 'score', 'chunk_index'],
                ],
                'not_found',
                'top_score',
                'model',
                'latency_ms',
            ]);

        $this->assertNotEmpty($response->json('sources.0.excerpt'));
        $this->assertNotNull($response->json('top_score'));

        $this->assertDatabaseHas('notulen_questions', [
            'question' => 'Apa keputusan anggaran?',
            'not_found' => 0,
            'user_id' => $user->id,
        ]);

        $logged = \App\Models\NotulenQuestion::query()->latest('id')->first();
        $this->assertNotNull($logged->top_score);
        $this->assertNotNull($logged->latency_ms);
        $this->assertSame('mock/chat', $logged->model);

        Http::assertSentCount(2);
    }

    public function test_can_scope_ask_to_meeting_ids(): void
    {
        $keep = Meeting::factory()->create(['title' => 'Keep Scope']);
        $skip = Meeting::factory()->create(['title' => 'Skip Scope']);

        MeetingChunk::factory()->create([
            'meeting_id' => $keep->id,
            'embedding' => [1.0, 0.0, 0.0, 0.0],
            'content' => 'Keep scope content about budget approval.',
        ]);
        MeetingChunk::factory()->create([
            'meeting_id' => $skip->id,
            'embedding' => [1.0, 0.0, 0.0, 0.0],
            'content' => 'Skip scope content about budget approval.',
        ]);

        $user = User::factory()->create();
        $user->givePermissionTo('akses_notulen');
        $this->stubOpenRouter();

        $this->actingAs($user)
            ->postJson(route('notulen.ask'), [
                'question' => 'Apa keputusan anggaran?',
                'meeting_ids' => [$keep->id],
            ])
            ->assertOk()
            ->assertJsonPath('not_found', false)
            ->assertJsonFragment(['title' => 'Keep Scope'])
            ->assertJsonMissing(['title' => 'Skip Scope']);
    }

    public function test_returns_not_found_when_below_similarity_threshold(): void
    {
        $meeting = Meeting::factory()->create();

        MeetingChunk::factory()->create([
            'meeting_id' => $meeting->id,
            'embedding' => [0.0, 1.0, 0.0, 0.0],
            'content' => 'Unrelated content.',
        ]);

        $user = User::factory()->create();
        $user->givePermissionTo('akses_notulen');

        $this->stubOpenRouter();

        $this->actingAs($user)
            ->postJson(route('notulen.ask'), [
                'question' => 'Totally unrelated?',
            ])
            ->assertOk()
            ->assertJsonPath('not_found', true);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'embeddings');
        });

        Http::assertNotSent(function ($request) {
            return str_contains($request->url(), 'chat/completions');
        });
    }
}
