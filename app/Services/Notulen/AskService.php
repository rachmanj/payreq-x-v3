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
     * @return array{
     *   answer: string,
     *   sources: array<int, array{id:int, title:string, meeting_date:?string, url:string}>,
     *   not_found: bool,
     * }
     */
    public function ask(string $question, bool $signedDownloadUrls = false): array
    {
        $picked = $this->retrieval->retrieve($question);

        if ($picked === []) {
            return [
                'answer' => 'Saya tidak menemukan informasi terkait pertanyaan ini dalam notulen rapat yang telah diindeks.',
                'sources' => [],
                'not_found' => true,
            ];
        }

        $contextParts = [];
        $meetingIds = [];

        foreach ($picked as $item) {
            $meeting = $item['meeting'];
            $chunk = $item['chunk'];
            $dateLabel = $meeting->meeting_date?->format('Y-m-d') ?? 'tanggal tidak diketahui';

            $contextParts[] = "Meeting: {$meeting->title} ({$dateLabel})\n{$chunk->content}";
            $meetingIds[$meeting->id] = $meeting;
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

        $answer = $this->client->chat([
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $question],
        ]);

        $sources = [];
        foreach ($meetingIds as $meeting) {
            $sources[] = $this->formatSource($meeting, $signedDownloadUrls);
        }

        return [
            'answer' => $answer,
            'sources' => $sources,
            'not_found' => false,
        ];
    }

    /**
     * @return array{id:int, title:string, meeting_date:?string, url:string}
     */
    protected function formatSource(Meeting $meeting, bool $signedDownloadUrls): array
    {
        $url = $signedDownloadUrls
            ? URL::temporarySignedRoute('notulen.meetings.download', now()->addHour(), ['meeting' => $meeting->id])
            : route('notulen.meetings.download', $meeting);

        return [
            'id' => $meeting->id,
            'title' => $meeting->title,
            'meeting_date' => $meeting->meeting_date?->format('Y-m-d'),
            'url' => $url,
        ];
    }
}
