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

        $built = $this->buildRetrievalContext($picked, $signedDownloadUrls);

        $model = config('services.openrouter.notulen_model');
        $answer = $this->client->chat([
            ['role' => 'system', 'content' => $built['system_prompt']],
            ['role' => 'user', 'content' => $question],
        ]);

        return [
            'answer' => $answer,
            'sources' => $built['sources'],
            'not_found' => false,
            'top_score' => $built['top_score'],
            'model' => is_string($model) ? $model : null,
            'latency_ms' => $this->elapsedMs($started),
        ];
    }

    /**
     * @param  array{
     *   meeting_ids?: array<int, int>,
     *   date_from?: string|null,
     *   date_to?: string|null,
     * }  $filters
     * @return array<int, string>
     */
    public function suggestions(string $question, string $answer, array $filters = []): array
    {
        unset($filters);

        $prompt = <<<PROMPT
Berdasarkan pertanyaan {$question} dan jawaban {$answer}, berikan 3 pertanyaan lanjutan yang berguna, satu per baris, awali '- ', bahasa sama dgn pertanyaan. Jangan tambahkan teks lain.
PROMPT;

        $result = $this->client->chat([
            ['role' => 'user', 'content' => $prompt],
        ]);

        $suggestions = [];
        foreach (preg_split('/\R/u', trim($result)) ?: [] as $line) {
            $line = trim($line);
            if (preg_match('/^-\s+(.+)/u', $line, $matches) === 1) {
                $suggestions[] = trim($matches[1]);
            }
        }

        return array_values(array_slice($suggestions, 0, 3));
    }

    /**
     * @param  array<int, array{meeting: Meeting, chunk: \App\Models\MeetingChunk, score: float}>  $picked
     * @return array{
     *   context: string,
     *   sources: array<int, array{id:int, title:string, meeting_date:?string, url:string, excerpt:?string, score:?float, chunk_index:?int}>,
     *   top_score: ?float,
     *   system_prompt: string,
     * }
     */
    public function buildRetrievalContext(array $picked, bool $signedDownloadUrls = false): array
    {
        $contextParts = [];
        $orderedSources = [];
        $sourceIndexByMeeting = [];
        $topScore = null;

        foreach ($picked as $item) {
            $meeting = $item['meeting'];
            $chunk = $item['chunk'];
            $score = (float) $item['score'];
            $topScore = $topScore === null ? $score : max($topScore, $score);
            $dateLabel = $meeting->meeting_date?->format('Y-m-d') ?? 'tanggal tidak diketahui';

            if (! isset($sourceIndexByMeeting[$meeting->id])) {
                $sourceIndexByMeeting[$meeting->id] = count($orderedSources) + 1;
                $orderedSources[] = $this->formatSource(
                    $meeting,
                    $signedDownloadUrls,
                    $chunk->content,
                    $score,
                    $chunk->chunk_index
                );
            } else {
                $sourceIdx = $sourceIndexByMeeting[$meeting->id] - 1;
                if ($score > ($orderedSources[$sourceIdx]['score'] ?? 0)) {
                    $orderedSources[$sourceIdx]['excerpt'] = $this->excerpt($chunk->content);
                    $orderedSources[$sourceIdx]['score'] = round($score, 4);
                    $orderedSources[$sourceIdx]['chunk_index'] = $chunk->chunk_index;
                }
            }

            $sourceNum = $sourceIndexByMeeting[$meeting->id];
            $contextParts[] = "Source {$sourceNum} — Meeting: {$meeting->title} ({$dateLabel})\n{$chunk->content}";
        }

        $context = implode("\n\n---\n\n", $contextParts);

        return [
            'context' => $context,
            'sources' => $orderedSources,
            'top_score' => $topScore !== null ? round($topScore, 4) : null,
            'system_prompt' => $this->systemPrompt($context),
        ];
    }

    protected function systemPrompt(string $context): string
    {
        return <<<PROMPT
You are a meeting-minutes assistant. Your knowledge is STRICTLY LIMITED to the CONTEXT below from uploaded meeting PDFs.
Rules:
1. Answer only using information explicitly present in CONTEXT. If CONTEXT does not contain the answer, say you found nothing relevant.
2. Cite meeting title and date when referencing specific discussions.
3. Write the answer in the same language as the user's question (Indonesian or English).
4. Do not invent meetings, dates, decisions, or participants not in CONTEXT.
5. When you refer to information from a source, append its source number in square brackets, e.g. [1]. If multiple sources: [1][2].
CONTEXT:
{$context}
PROMPT;
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
