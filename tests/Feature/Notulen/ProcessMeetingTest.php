<?php

namespace Tests\Feature\Notulen;

use App\Jobs\ProcessMeeting;
use App\Models\Meeting;
use App\Models\MeetingChunk;
use App\Services\Notulen\PdfExtractionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProcessMeetingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('notulen');

        config([
            'services.openrouter.api_key' => 'test-key',
            'services.openrouter.base_url' => 'https://openrouter.ai/api/v1',
            'services.openrouter.embedding_model' => 'mock/model',
        ]);

        Http::fake([
            'https://openrouter.ai/api/v1/embeddings*' => Http::response([
                'data' => [
                    ['embedding' => [1.0, 0.0, 0.0, 0.0], 'index' => 0],
                    ['embedding' => [0.9, 0.1, 0.0, 0.0], 'index' => 1],
                ],
            ], 200),
        ]);
    }

    public function test_process_meeting_extracts_chunks_and_marks_processed(): void
    {
        $meeting = Meeting::factory()->pending()->create([
            'file_path' => 'sample.pdf',
        ]);

        Storage::disk('notulen')->put('sample.pdf', 'dummy');

        $this->mock(PdfExtractionService::class, function ($mock): void {
            $mock->shouldReceive('extractFromPath')
                ->once()
                ->andReturn(str_repeat('Rapat membahas anggaran dan keputusan strategis. ', 80));
        });

        $job = new ProcessMeeting($meeting);
        $job->handle(
            app(PdfExtractionService::class),
            app(\App\Services\Notulen\NotulenChunker::class),
            app(\App\Services\Notulen\NotulenOpenRouterClient::class),
        );

        $meeting->refresh();

        $this->assertSame(Meeting::STATUS_PROCESSED, $meeting->status);
        $this->assertNotNull($meeting->full_text);
        $this->assertGreaterThan(0, MeetingChunk::query()->where('meeting_id', $meeting->id)->count());
    }

    public function test_process_meeting_marks_failed_when_file_missing(): void
    {
        $meeting = Meeting::factory()->pending()->create([
            'file_path' => 'missing.pdf',
        ]);

        $job = new ProcessMeeting($meeting);
        $job->handle(
            app(PdfExtractionService::class),
            app(\App\Services\Notulen\NotulenChunker::class),
            app(\App\Services\Notulen\NotulenOpenRouterClient::class),
        );

        $this->assertSame(Meeting::STATUS_FAILED, $meeting->fresh()->status);
    }
}
