<?php

namespace App\Jobs;

use App\Models\Meeting;
use App\Models\MeetingChunk;
use App\Services\Notulen\NotulenChunker;
use App\Services\Notulen\NotulenOpenRouterClient;
use App\Services\Notulen\PdfExtractionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProcessMeeting implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Meeting $meeting,
    ) {}

    public function handle(
        PdfExtractionService $pdfExtraction,
        NotulenChunker $chunker,
        NotulenOpenRouterClient $client,
    ): void {
        $disk = Storage::disk('notulen');

        if (! $disk->exists($this->meeting->file_path)) {
            $this->meeting->update(['status' => Meeting::STATUS_FAILED]);

            return;
        }

        try {
            $absolutePath = $disk->path($this->meeting->file_path);
            $fullText = $pdfExtraction->extractFromPath($absolutePath);

            if ($fullText === '') {
                $this->meeting->update([
                    'status' => Meeting::STATUS_FAILED,
                    'full_text' => null,
                ]);

                return;
            }

            $this->meeting->update([
                'full_text' => $fullText,
            ]);

            $textChunks = $chunker->chunk($fullText);
            if ($textChunks === []) {
                $this->meeting->update(['status' => Meeting::STATUS_FAILED]);

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

            $this->meeting->update(['status' => Meeting::STATUS_PROCESSED]);
        } catch (Throwable $exception) {
            Log::error('ProcessMeeting failed', [
                'meeting_id' => $this->meeting->id,
                'message' => $exception->getMessage(),
            ]);

            $this->meeting->update(['status' => Meeting::STATUS_FAILED]);
        }
    }
}
