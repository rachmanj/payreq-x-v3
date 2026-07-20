<?php

namespace Tests\Feature\Notulen;

use App\Exceptions\OpenRouterException;
use App\Jobs\ProcessMeeting;
use App\Models\Meeting;
use App\Models\MeetingChunk;
use App\Services\Notulen\NotulenChunker;
use App\Services\Notulen\NotulenOpenRouterClient;
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
            app(NotulenChunker::class),
            app(NotulenOpenRouterClient::class),
        );

        $meeting->refresh();

        $this->assertSame(Meeting::STATUS_PROCESSED, $meeting->status);
        $this->assertNull($meeting->error_message);
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
            app(NotulenChunker::class),
            app(NotulenOpenRouterClient::class),
        );

        $meeting->refresh();

        $this->assertSame(Meeting::STATUS_FAILED, $meeting->status);
        $this->assertNotNull($meeting->error_message);
        $this->assertStringContainsString('not found', $meeting->error_message);
    }

    public function test_process_meeting_marks_failed_when_extraction_empty(): void
    {
        $meeting = Meeting::factory()->pending()->create([
            'file_path' => 'empty.pdf',
        ]);

        Storage::disk('notulen')->put('empty.pdf', 'dummy');

        $this->mock(PdfExtractionService::class, function ($mock): void {
            $mock->shouldReceive('extractFromPath')
                ->once()
                ->andReturn('');
        });

        $job = new ProcessMeeting($meeting);
        $job->handle(
            app(PdfExtractionService::class),
            app(NotulenChunker::class),
            app(NotulenOpenRouterClient::class),
        );

        $meeting->refresh();

        $this->assertSame(Meeting::STATUS_FAILED, $meeting->status);
        $this->assertStringContainsString('No text could be extracted', $meeting->error_message);
    }

    public function test_process_meeting_rethrows_openrouter_exception_for_retry(): void
    {
        $meeting = Meeting::factory()->pending()->create([
            'file_path' => 'retry.pdf',
        ]);

        Storage::disk('notulen')->put('retry.pdf', 'dummy');

        $this->mock(PdfExtractionService::class, function ($mock): void {
            $mock->shouldReceive('extractFromPath')
                ->once()
                ->andReturn(str_repeat('Rapat membahas anggaran. ', 40));
        });

        $this->mock(NotulenOpenRouterClient::class, function ($mock): void {
            $mock->shouldReceive('embedMany')
                ->once()
                ->andThrow(new OpenRouterException('rate limited', 429));
        });

        $job = new ProcessMeeting($meeting);

        $this->expectException(OpenRouterException::class);

        $job->handle(
            app(PdfExtractionService::class),
            app(NotulenChunker::class),
            app(NotulenOpenRouterClient::class),
        );
    }

    public function test_failed_method_persists_error_message(): void
    {
        $meeting = Meeting::factory()->pending()->create([
            'file_path' => 'fail.pdf',
            'status' => Meeting::STATUS_PROCESSING,
        ]);

        $job = new ProcessMeeting($meeting);
        $job->failed(new OpenRouterException('Unable to reach OpenRouter.', 503));

        $meeting->refresh();

        $this->assertSame(Meeting::STATUS_FAILED, $meeting->status);
        $this->assertStringContainsString('OpenRouter', $meeting->error_message);
    }

    public function test_job_has_retry_and_backoff_configuration(): void
    {
        $job = new ProcessMeeting(Meeting::factory()->pending()->make());

        $this->assertSame(3, $job->tries);
        $this->assertSame(300, $job->timeout);
        $this->assertSame([30, 120, 300], $job->backoff());
    }
}
