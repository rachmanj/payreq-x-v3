<?php

namespace App\Services\Notulen;

use App\Models\MeetingChunk;
use App\Services\Help\HelpVector;

class RetrievalService
{
    public function __construct(
        protected NotulenOpenRouterClient $client,
    ) {}

    /**
     * @return array<int, array{
     *   score: float,
     *   chunk: MeetingChunk,
     *   meeting: \App\Models\Meeting,
     * }>
     */
    public function retrieve(string $question): array
    {
        $rows = MeetingChunk::query()
            ->with('meeting')
            ->whereHas('meeting', fn ($q) => $q->where('status', 'processed'))
            ->get();

        if ($rows->isEmpty()) {
            return [];
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
}
