<?php

namespace Tests\Feature\Notulen;

use App\Models\Meeting;
use App\Models\MeetingChunk;
use App\Services\Notulen\RetrievalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RetrievalServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.openrouter.api_key' => 'test-key',
            'services.openrouter.base_url' => 'https://openrouter.ai/api/v1',
            'services.openrouter.embedding_model' => 'mock/model',
            'notulen.similarity_threshold' => 0.22,
            'notulen.top_k' => 6,
            'notulen.max_chunks_scanned' => 5000,
            'notulen.chunk_cache_ttl' => 300,
        ]);

        Http::fake([
            'https://openrouter.ai/api/v1/embeddings*' => Http::response([
                'data' => [['embedding' => [1.0, 0.0, 0.0, 0.0], 'index' => 0]],
            ], 200),
        ]);
    }

    public function test_scopes_retrieval_by_meeting_id(): void
    {
        $keep = Meeting::factory()->create(['title' => 'Keep']);
        $skip = Meeting::factory()->create(['title' => 'Skip']);

        MeetingChunk::factory()->create([
            'meeting_id' => $keep->id,
            'embedding' => [1.0, 0.0, 0.0, 0.0],
            'content' => 'Keep meeting content about budget.',
        ]);

        MeetingChunk::factory()->create([
            'meeting_id' => $skip->id,
            'embedding' => [1.0, 0.0, 0.0, 0.0],
            'content' => 'Skip meeting content about budget.',
        ]);

        $results = app(RetrievalService::class)->retrieve('budget', [
            'meeting_ids' => [$keep->id],
        ]);

        $this->assertCount(1, $results);
        $this->assertSame($keep->id, $results[0]['meeting']->id);
    }

    public function test_scopes_retrieval_by_date_range(): void
    {
        $inRange = Meeting::factory()->create([
            'meeting_date' => '2026-02-15',
        ]);
        $outOfRange = Meeting::factory()->create([
            'meeting_date' => '2025-01-01',
        ]);

        MeetingChunk::factory()->create([
            'meeting_id' => $inRange->id,
            'embedding' => [1.0, 0.0, 0.0, 0.0],
            'content' => 'February meeting.',
        ]);
        MeetingChunk::factory()->create([
            'meeting_id' => $outOfRange->id,
            'embedding' => [1.0, 0.0, 0.0, 0.0],
            'content' => 'Old meeting.',
        ]);

        $results = app(RetrievalService::class)->retrieve('meeting', [
            'date_from' => '2026-01-01',
            'date_to' => '2026-12-31',
        ]);

        $this->assertCount(1, $results);
        $this->assertSame($inRange->id, $results[0]['meeting']->id);
    }

    public function test_clear_chunk_cache_invalidates_cached_rows(): void
    {
        Cache::flush();

        $meeting = Meeting::factory()->create();
        MeetingChunk::factory()->create([
            'meeting_id' => $meeting->id,
            'embedding' => [1.0, 0.0, 0.0, 0.0],
            'content' => 'Cached content.',
        ]);

        $service = app(RetrievalService::class);
        $first = $service->retrieve('Cached');
        $this->assertCount(1, $first);

        MeetingChunk::query()->delete();
        RetrievalService::clearChunkCache();

        $second = $service->retrieve('Cached');
        $this->assertCount(0, $second);
    }
}
