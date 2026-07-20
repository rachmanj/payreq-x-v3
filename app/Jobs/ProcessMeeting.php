<?php

namespace App\Jobs;

use App\Exceptions\NotulenOcrException;
use App\Exceptions\OpenRouterException;
use App\Models\Meeting;
use App\Models\MeetingChunk;
use App\Services\Notulen\NotulenChunker;
use App\Services\Notulen\NotulenOpenRouterClient;
use App\Services\Notulen\PdfExtractionService;
use App\Services\Notulen\RetrievalService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProcessMeeting implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 300;

    public function __construct(
        public Meeting $meeting,
    ) {}

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [30, 120, 300];
    }

    public function handle(
        PdfExtractionService $pdfExtraction,
        NotulenChunker $chunker,
        NotulenOpenRouterClient $client,
    ): void {
        $disk = Storage::disk('notulen');

        if (! $disk->exists($this->meeting->file_path)) {
            $this->meeting->update([
                'status' => Meeting::STATUS_FAILED,
                'error_message' => 'PDF file not found on storage disk.',
            ]);

            return;
        }

        $this->meeting->update([
            'status' => Meeting::STATUS_PROCESSING,
            'error_message' => null,
        ]);

        try {
            $absolutePath = $disk->path($this->meeting->file_path);
            $fullText = $pdfExtraction->extractFromPath($absolutePath);

            if ($fullText === '') {
                $this->meeting->update([
                    'status' => Meeting::STATUS_FAILED,
                    'full_text' => null,
                    'error_message' => 'No text could be extracted from the PDF (native parser and OCR both returned empty).',
                ]);

                return;
            }

            $this->meeting->update([
                'full_text' => $fullText,
            ]);

            $textChunks = $chunker->chunk($fullText);
            if ($textChunks === []) {
                $this->meeting->update([
                    'status' => Meeting::STATUS_FAILED,
                    'error_message' => 'Extracted text was empty after chunking.',
                ]);

                return;
            }

            $this->meeting->chunks()->delete();

            $batchSize = max(1, (int) config('notulen.embed_batch_size'));
            $index = 0;

            foreach (array_chunk($textChunks, $batchSize) as $batch) {
                $vectors = $client->embedMany($batch);

                foreach ($batch as $i => $content) {
                    MeetingChunk::query()->create([
                        'meeting_id' => $this->meeting->id,
                        'chunk_index' => $index,
                        'content' => $content,
                        'embedding' => $vectors[$i] ?? [],
                    ]);
                    $index++;
                }
            }

            $this->meeting->update([
                'status' => Meeting::STATUS_PROCESSED,
                'error_message' => null,
            ]);

            RetrievalService::clearChunkCache();
        } catch (NotulenOcrException $exception) {
            Log::error('ProcessMeeting OCR guard failed', [
                'meeting_id' => $this->meeting->id,
                'message' => $exception->getMessage(),
            ]);

            $this->meeting->update([
                'status' => Meeting::STATUS_FAILED,
                'error_message' => $exception->getMessage(),
            ]);
        } catch (OpenRouterException|ConnectionException $exception) {
            Log::warning('ProcessMeeting transient OpenRouter failure', [
                'meeting_id' => $this->meeting->id,
                'attempt' => $this->attempts(),
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        } catch (Throwable $exception) {
            Log::error('ProcessMeeting failed', [
                'meeting_id' => $this->meeting->id,
                'message' => $exception->getMessage(),
            ]);

            $this->meeting->update([
                'status' => Meeting::STATUS_FAILED,
                'error_message' => $this->formatErrorMessage($exception),
            ]);
        }
    }

    public function failed(?Throwable $exception): void
    {
        $this->meeting->update([
            'status' => Meeting::STATUS_FAILED,
            'error_message' => $exception
                ? $this->formatErrorMessage($exception)
                : 'Processing failed after all retry attempts.',
        ]);
    }

    protected function formatErrorMessage(Throwable $exception): string
    {
        if ($exception instanceof ConnectionException) {
            return 'Unable to reach OpenRouter API (connection error).';
        }

        if ($exception instanceof OpenRouterException) {
            $status = $exception->getStatusCode();
            if ($status === 429) {
                return 'OpenRouter rate limit exceeded. Please reprocess later.';
            }

            if ($status >= 500 || $status === 503) {
                return 'OpenRouter API unavailable: '.$exception->getMessage();
            }

            return 'OpenRouter error: '.$exception->getMessage();
        }

        return mb_substr($exception->getMessage(), 0, 1000, 'UTF-8');
    }
}
