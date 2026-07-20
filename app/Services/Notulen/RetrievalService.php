<?php

namespace App\Services\Notulen;

use App\Models\Meeting;
use App\Models\MeetingChunk;
use App\Services\Help\HelpVector;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class RetrievalService
{
    public function __construct(
        protected NotulenOpenRouterClient $client,
    ) {}

    /**
     * @param  array{
     *   meeting_ids?: array<int, int>,
     *   date_from?: string|null,
     *   date_to?: string|null,
     * }  $filters
     * @return array<int, array{
     *   score: float,
     *   chunk: MeetingChunk,
     *   meeting: Meeting,
     * }>
     */
    public function retrieve(string $question, array $filters = []): array
    {
        $rows = $this->loadChunks($filters);

        if ($rows->isEmpty()) {
            return [];
        }

        $maxChunks = max(1, (int) config('notulen.max_chunks_scanned', 5000));
        if ($rows->count() > $maxChunks) {
            $rows = $rows->take($maxChunks);
        }

        $queryVector = $this->client->embed($question);
        $threshold = (float) config('notulen.similarity_threshold');
        $topK = max(1, (int) config('notulen.top_k'));

        $scored = [];
        foreach ($rows as $row) {
            /** @var array<int, float> $vec */
            $vec = $row->embedding ?? [];
            $score = HelpVector::cosineSimilarity($queryVector, $vec);

            $scored[] = [
                'score' => $score,
                'chunk' => $row,
                'meeting' => $row->meeting,
            ];
        }

        usort($scored, static function ($a, $b) {
            $scoreCmp = $b['score'] <=> $a['score'];
            if ($scoreCmp !== 0) {
                return $scoreCmp;
            }

            $dateA = $a['meeting']?->meeting_date;
            $dateB = $b['meeting']?->meeting_date;

            return ($dateB?->timestamp ?? 0) <=> ($dateA?->timestamp ?? 0);
        });

        $bestScore = $scored[0]['score'] ?? 0.0;
        if ($bestScore < $threshold) {
            return [];
        }

        return array_slice($scored, 0, $topK);
    }

    public static function clearChunkCache(): void
    {
        Cache::forget(self::cacheKey());
    }

    protected static function cacheKey(): string
    {
        $fingerprint = Meeting::query()
            ->where('status', Meeting::STATUS_PROCESSED)
            ->selectRaw('COUNT(*) as cnt, COALESCE(MAX(updated_at), "0") as max_updated')
            ->first();

        $cnt = $fingerprint->cnt ?? 0;
        $maxUpdated = $fingerprint->max_updated ?? '0';

        return 'notulen:chunks:'.$cnt.':'.md5((string) $maxUpdated);
    }

    /**
     * @param  array{
     *   meeting_ids?: array<int, int>,
     *   date_from?: string|null,
     *   date_to?: string|null,
     * }  $filters
     * @return Collection<int, MeetingChunk>
     */
    protected function loadChunks(array $filters): Collection
    {
        $hasFilters = ! empty($filters['meeting_ids'])
            || filled($filters['date_from'] ?? null)
            || filled($filters['date_to'] ?? null);

        if ($hasFilters) {
            return $this->queryChunks($filters)->get();
        }

        $ttl = max(1, (int) config('notulen.chunk_cache_ttl', 300));

        return Cache::remember(self::cacheKey(), $ttl, function () {
            return $this->queryChunks([])->get();
        });
    }

    /**
     * @param  array{
     *   meeting_ids?: array<int, int>,
     *   date_from?: string|null,
     *   date_to?: string|null,
     * }  $filters
     * @return \Illuminate\Database\Eloquent\Builder<MeetingChunk>
     */
    protected function queryChunks(array $filters)
    {
        return MeetingChunk::query()
            ->with('meeting')
            ->whereHas('meeting', function ($q) use ($filters) {
                $q->where('status', Meeting::STATUS_PROCESSED);

                if (! empty($filters['meeting_ids'])) {
                    $q->whereIn('id', $filters['meeting_ids']);
                }

                if (filled($filters['date_from'] ?? null)) {
                    $q->whereDate('meeting_date', '>=', $filters['date_from']);
                }

                if (filled($filters['date_to'] ?? null)) {
                    $q->whereDate('meeting_date', '<=', $filters['date_to']);
                }
            })
            ->orderByDesc('id');
    }
}
