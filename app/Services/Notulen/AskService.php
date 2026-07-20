<?php

namespace App\Services\Notulen;

use App\Models\Meeting;
use Illuminate\Support\Facades\URL;

class AskService
{
    public function __construct(
        protected NotulenOpenRouterClient $client,
        protected RetrievalService $retrieval,
    ) {}

    /**
     * @param  array{
     *   meeting_ids?: array<int, int>,
     *   date_from?: string|null,
     *   date_to?: string|null,
     * }  $filters
     * @return array{
     *   answer: string,
     *   sources: array<int, array{id:int, title:string, meeting_date:?string, url:string, excerpt:?string, score:?float, chunk_index:?int}>,
     *   not_found: bool,
     *   top_score: ?float,
     *   model: ?string,
     *   latency_ms: int,
     * }
     */
    public function ask(string $question, bool $signedDownloadUrls = false, array $filters = []): array
    {
        $started = hrtime(true);
        $picked = $this->retrieval->retrieve($question, $filters);

        if ($picked === []) {
            return [
                'answer' => $this->notFoundMessage($question),
                'sources' => [],
                'not_found' => true,
                'top_score' => null,
                'model' => null,
                'latency_ms' => $this->elapsedMs($started),
            ];
        }

        $contextParts = [];
        $sourcesByMeeting = [];
        $topScore = null;

        foreach ($picked as $item) {
            $meeting = $item['meeting'];
            $chunk = $item['chunk'];
            $score = (float) $item['score'];
            $topScore = $topScore === null ? $score : max($topScore, $score);
            $dateLabel = $meeting->meeting_date?->format('Y-m-d') ?? 'tanggal tidak diketahui';

            $contextParts[] = "Meeting: {$meeting->title} ({$dateLabel})\n{$chunk->content}";

            if (! isset($sourcesByMeeting[$meeting->id])) {
                $sourcesByMeeting[$meeting->id] = $this->formatSource(
                    $meeting,
                    $signedDownloadUrls,
                    $chunk->content,
                    $score,
                    $chunk->chunk_index
                );
            } elseif ($score > ($sourcesByMeeting[$meeting->id]['score'] ?? 0)) {
                $sourcesByMeeting[$meeting->id]['excerpt'] = $this->excerpt($chunk->content);
                $sourcesByMeeting[$meeting->id]['score'] = round($score, 4);
                $sourcesByMeeting[$meeting->id]['chunk_index'] = $chunk->chunk_index;
            }
        }

        $context = implode("\n\n---\n\n", $contextParts);

        $systemPrompt = <<<PROMPT
You are a meeting-minutes assistant. Your knowledge is STRICTLY LIMITED to the CONTEXT below from uploaded meeting PDFs.
Rules:
1. Answer only using information explicitly present in CONTEXT. If CONTEXT does not contain the answer, say you found nothing relevant.
2. Cite meeting title and date when referencing specific discussions.
3. Write the answer in the same language as the user's question (Indonesian or English).
4. Do not invent meetings, dates, decisions, or participants not in CONTEXT.
CONTEXT:
{$context}
PROMPT;

        $model = config('services.openrouter.notulen_model');
        $answer = $this->client->chat([
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $question],
        ]);

        return [
            'answer' => $answer,
            'sources' => array_values($sourcesByMeeting),
            'not_found' => false,
            'top_score' => $topScore !== null ? round($topScore, 4) : null,
            'model' => is_string($model) ? $model : null,
            'latency_ms' => $this->elapsedMs($started),
        ];
    }

    protected function notFoundMessage(string $question): string
    {
        if ($this->looksIndonesian($question)) {
            return 'Saya tidak menemukan informasi terkait pertanyaan ini dalam notulen rapat yang telah diindeks.';
        }

        return 'I could not find relevant information for this question in the indexed meeting minutes.';
    }

    protected function looksIndonesian(string $text): bool
    {
        $lower = mb_strtolower($text, 'UTF-8');
        $markers = ['apa', 'siapa', 'kapan', 'bagaimana', 'mengapa', 'berapa', 'apakah', 'rapat', 'keputusan', 'notulen', 'yang', 'dari', 'untuk', 'dengan', 'adalah'];

        foreach ($markers as $marker) {
            if (preg_match('/\b'.preg_quote($marker, '/').'\b/u', $lower) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{id:int, title:string, meeting_date:?string, url:string, excerpt:?string, score:?float, chunk_index:?int}
     */
    protected function formatSource(
        Meeting $meeting,
        bool $signedDownloadUrls,
        ?string $content = null,
        ?float $score = null,
        ?int $chunkIndex = null,
    ): array {
        $url = $signedDownloadUrls
            ? URL::temporarySignedRoute('notulen.meetings.download', now()->addHour(), ['meeting' => $meeting->id])
            : route('notulen.meetings.download', $meeting);

        return [
            'id' => $meeting->id,
            'title' => $meeting->title,
            'meeting_date' => $meeting->meeting_date?->format('Y-m-d'),
            'url' => $url,
            'excerpt' => $content !== null ? $this->excerpt($content) : null,
            'score' => $score !== null ? round($score, 4) : null,
            'chunk_index' => $chunkIndex,
        ];
    }

    protected function excerpt(string $content, int $max = 240): string
    {
        $content = trim(preg_replace('/\s+/u', ' ', $content) ?? $content);
        if (mb_strlen($content, 'UTF-8') <= $max) {
            return $content;
        }

        return rtrim(mb_substr($content, 0, $max - 1, 'UTF-8')).'…';
    }

    protected function elapsedMs(int $started): int
    {
        return (int) round((hrtime(true) - $started) / 1_000_000);
    }
}
