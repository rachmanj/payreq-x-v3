<?php

namespace Tests\Feature\Help;

use App\Models\HelpEmbedding;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HelpAskTest extends TestCase
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
            'services.openrouter.help_model' => 'mock/chat',
        ]);

        Http::fake([
            'https://openrouter.ai/api/v1/embeddings*' => Http::response([
                'data' => [['embedding' => [1.0, 0.0, 0.0, 0.0], 'index' => 0]],
            ], 200),
            'https://openrouter.ai/api/v1/chat/completions*' => Http::response([
                'choices' => [['message' => ['content' => 'Answer from manual.']]],
            ], 200),
        ]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->stubOpenRouter();

        $this->post(route('help.ask'), [
            'message' => 'test',
            'locale' => 'auto',
        ])->assertRedirect(route('login'));
    }

    public function test_forbidden_when_user_has_no_permission(): void
    {
        $user = User::factory()->create();
        $this->stubOpenRouter();

        $this->actingAs($user)
            ->postJson(route('help.ask'), [
                'message' => 'How?',
                'locale' => 'auto',
            ])
            ->assertForbidden();
    }

    public function test_returns_not_documented_when_no_embeddings(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('akses_help');

        $this->stubOpenRouter();

        $this->actingAs($user)
            ->postJson(route('help.ask'), [
                'message' => 'How?',
                'locale' => 'en',
            ])
            ->assertOk()
            ->assertJsonPath('not_documented', true);
    }

    public function test_returns_answer_when_similarity_above_threshold(): void
    {
        HelpEmbedding::factory()->create([
            'embedding' => [1.0, 0.0, 0.0, 0.0],
            'locale' => 'en',
            'source_path' => 'docs/manuals/getting-started-en.md',
            'heading' => 'Introduction',
            'chunk_key' => hash('sha256', 'getting-started-intro'),
            'content' => 'Manual content describing the feature.',
        ]);

        $user = User::factory()->create();
        $user->givePermissionTo('akses_help');

        $this->stubOpenRouter();

        $this->actingAs($user)
            ->postJson(route('help.ask'), [
                'message' => 'How does this feature work?',
                'locale' => 'en',
            ])
            ->assertOk()
            ->assertJsonPath('not_documented', false)
            ->assertJsonFragment(['answer' => 'Answer from manual.']);

        Http::assertSentCount(2);
    }

    public function test_returns_not_documented_when_below_similarity_threshold(): void
    {
        HelpEmbedding::factory()->create([
            'embedding' => [0.0, 1.0, 0.0, 0.0],
            'locale' => 'en',
        ]);

        $user = User::factory()->create();
        $user->givePermissionTo('akses_help');

        $this->stubOpenRouter();

        $response = $this->actingAs($user)
            ->postJson(route('help.ask'), [
                'message' => 'Unrelated?',
                'locale' => 'en',
            ]);

        $response->assertOk()
            ->assertJsonPath('not_documented', true);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'embeddings');
        });

        Http::assertNotSent(function ($request) {
            return str_contains($request->url(), 'chat/completions');
        });
    }
}
