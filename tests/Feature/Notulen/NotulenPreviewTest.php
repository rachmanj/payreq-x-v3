<?php

namespace Tests\Feature\Notulen;

use App\Models\Meeting;
use App\Models\MeetingChunk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class NotulenPreviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('notulen');
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

    public function test_preview_returns_inline_disposition_for_pdf(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('akses_notulen');

        $meeting = Meeting::factory()->create([
            'original_filename' => 'notulen-rapat.pdf',
            'file_path' => 'sample.pdf',
        ]);

        Storage::disk('notulen')->put('sample.pdf', '%PDF-1.4 fake content');

        $response = $this->actingAs($user)
            ->get(route('notulen.meetings.preview', $meeting));

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertStringContainsString(
            'inline',
            strtolower((string) $response->headers->get('Content-Disposition'))
        );
    }

    public function test_preview_is_forbidden_without_login_or_permission(): void
    {
        $meeting = Meeting::factory()->create([
            'original_filename' => 'notulen-rapat.pdf',
            'file_path' => 'sample.pdf',
        ]);

        Storage::disk('notulen')->put('sample.pdf', '%PDF-1.4 fake content');

        $this->get(route('notulen.meetings.preview', $meeting))
            ->assertForbidden();

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('notulen.meetings.preview', $meeting))
            ->assertForbidden();
    }

    public function test_ask_sources_use_preview_url(): void
    {
        $meeting = Meeting::factory()->create([
            'title' => 'Rapat Preview',
            'meeting_date' => '2026-01-15',
            'original_filename' => 'notulen-rapat.pdf',
            'file_path' => 'sample.pdf',
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
            ->assertOk();

        $url = $response->json('sources.0.url');
        $this->assertIsString($url);
        $this->assertStringContainsString('/notulen/meetings/', $url);
        $this->assertStringContainsString('/preview', $url);
    }
}
