<?php

namespace App\Services\Help;

use App\Models\HelpEmbedding;

class HelpAssistantService
{
    public function __construct(
        protected HelpOpenRouterClient $client,
    ) {}

    /**
     * @return array{
     *   answer: string,
     *   sources: array<int, array{title:string, path:string, heading:?string}>,
     *   not_documented: bool,
     * }
     */
    public function ask(string $message, ?string $locale): array
    {
        $userLocale = $this->resolvedLocale($locale);

        /** @var \Illuminate\Database\Eloquent\Collection<int, HelpEmbedding> $rows */
        $rows = HelpEmbedding::query()->get();

        if ($rows->isEmpty()) {
            return [
                'answer' => $this->nothingIndexedMessage(),
                'sources' => [],
                'not_documented' => true,
            ];
        }

        $queryVector = $this->client->embed($message);

        $threshold = (float) config('help.similarity_threshold');
        $topK = max(1, (int) config('help.top_k'));
        $boostMatch = (float) config('help.locale_boost_match');
        $boostBoth = (float) config('help.locale_boost_both');

        $scored = [];
        foreach ($rows as $row) {
            /** @var array<int, float> $vec */
            $vec = $row->embedding ?? [];
            $score = HelpVector::cosineSimilarity($queryVector, $vec);

            if ($row->locale === $userLocale) {
                $score += $boostMatch;
            } elseif ($row->locale === 'both') {
                $score += $boostBoth;
            }

            $scored[] = [
                'score' => $score,
                'row' => $row,
            ];
        }

        usort($scored, static fn ($a, $b) => $b['score'] <=> $a['score']);

        $bestScore = $scored[0]['score'] ?? 0.0;
        if ($bestScore < $threshold) {
            return [
                'answer' => $this->notDocumentedMessage(),
                'sources' => [],
                'not_documented' => true,
            ];
        }

        $picked = array_slice($scored, 0, $topK);

        $contextParts = [];
        $sources = [];
        foreach ($picked as $item) {
            /** @var HelpEmbedding $row */
            $row = $item['row'];
            $headingLine = $row->heading !== null ? 'Heading: '.$row->heading."\n" : '';

            $contextParts[] = "Source: {$row->source_path}\n".$headingLine.$row->content;
            $sources[] = [
                'title' => basename($row->source_path),
                'path' => $row->source_path,
                'heading' => $row->heading,
            ];
        }

        $context = implode("\n\n---\n\n", $contextParts);
        $langHint = match ($userLocale) {
            'id' => 'Indonesian (Bahasa Indonesia)',
            default => 'English',
        };

        $systemPrompt = <<<PROMPT
You are an in-application help assistant for an accounting ERP. Your knowledge is STRICTLY LIMITED to CONTEXT below.
Rules:
1. Answer only using information explicitly present in CONTEXT. If CONTEXT does not say how menus, buttons, or fields work, reply that those details are not in the manuals.
2. Do not infer features, shortcuts, transactions, SAP behavior, balances, KPIs or live numbers.
3. Write the answer entirely in {$langHint}.
4. Prefer step-by-step instructions when CONTEXT supports them.
5. At the end, list Sources as short bullet titles from CONTEXT (manual filenames or headings). Do not fabricate filenames.
CONTEXT:
{$context}
PROMPT;

        $answer = $this->client->helpChat([
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $message],
        ]);

        return [
            'answer' => $answer,
            'sources' => $sources,
            'not_documented' => false,
        ];
    }

    protected function resolvedLocale(?string $locale): string
    {
        if ($locale === 'id' || $locale === 'en') {
            return $locale;
        }

        $app = strtolower(substr(app()->getLocale(), 0, 2));

        return in_array($app, ['id', 'en'], true) ? $app : 'en';
    }

    protected function nothingIndexedMessage(): string
    {
        return 'No help manuals are indexed yet. Run `php artisan help:reindex` on the server after adding documentation under docs/manuals/.';
    }

    protected function notDocumentedMessage(): string
    {
        return 'This topic is not covered in the indexed manuals, or the match was too uncertain. Ask an administrator or add detail to docs/manuals/ and run help:reindex.';
    }
}
