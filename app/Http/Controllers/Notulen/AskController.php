<?php

namespace App\Http\Controllers\Notulen;

use App\Exceptions\OpenRouterException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Notulen\AskAiQuestionRequest;
use App\Models\Meeting;
use App\Models\NotulenQuestion;
use App\Services\Notulen\AskService;
use App\Services\Notulen\NotulenOpenRouterClient;
use App\Services\Notulen\RetrievalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AskController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:akses_notulen');
    }

    public function index()
    {
        $meetings = Meeting::query()
            ->where('status', Meeting::STATUS_PROCESSED)
            ->orderByDesc('meeting_date')
            ->orderByDesc('created_at')
            ->get(['id', 'title', 'meeting_date']);

        return view('notulen.ask.index', [
            'meetings' => $meetings,
            'streamingEnabled' => (bool) config('notulen.streaming_enabled'),
        ]);
    }

    public function ask(AskAiQuestionRequest $request, AskService $askService): JsonResponse|StreamedResponse
    {
        $question = $request->validated('question');
        $filters = $request->filters();
        $wantsStream = (bool) $request->boolean('stream') && (bool) config('notulen.streaming_enabled');

        if ($wantsStream) {
            return $this->streamAsk($request, $question, $filters);
        }

        try {
            $result = $askService->ask($question, false, $filters);

            $this->logQuestion($request->user()->id, $question, $result);

            return response()->json($result);
        } catch (OpenRouterException $e) {
            return $this->openRouterError($e);
        }
    }

    /**
     * @param  array{
     *   meeting_ids?: array<int, int>,
     *   date_from?: string|null,
     *   date_to?: string|null,
     * }  $filters
     */
    protected function streamAsk(AskAiQuestionRequest $request, string $question, array $filters): StreamedResponse
    {
        $retrieval = app(RetrievalService::class);
        $client = app(NotulenOpenRouterClient::class);
        $askService = app(AskService::class);

        return response()->stream(function () use ($request, $question, $filters, $retrieval, $client, $askService) {
            $started = hrtime(true);

            try {
                $picked = $retrieval->retrieve($question, $filters);

                if ($picked === []) {
                    $result = $askService->ask($question, false, $filters);
                    $this->emitSse('result', $result);
                    $this->logQuestion($request->user()->id, $question, $result);

                    return;
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

                    if (! isset($sourcesByMeeting[$meeting->id]) || $score > ($sourcesByMeeting[$meeting->id]['score'] ?? 0)) {
                        $sourcesByMeeting[$meeting->id] = [
                            'id' => $meeting->id,
                            'title' => $meeting->title,
                            'meeting_date' => $meeting->meeting_date?->format('Y-m-d'),
                            'url' => route('notulen.meetings.download', $meeting),
                            'excerpt' => mb_strlen($chunk->content, 'UTF-8') > 240
                                ? rtrim(mb_substr(trim(preg_replace('/\s+/u', ' ', $chunk->content) ?? $chunk->content), 0, 239, 'UTF-8')).'…'
                                : trim(preg_replace('/\s+/u', ' ', $chunk->content) ?? $chunk->content),
                            'score' => round($score, 4),
                            'chunk_index' => $chunk->chunk_index,
                        ];
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

                $this->emitSse('meta', [
                    'sources' => array_values($sourcesByMeeting),
                    'top_score' => $topScore !== null ? round($topScore, 4) : null,
                    'model' => $client->chatModel(),
                    'not_found' => false,
                ]);

                $answer = '';
                foreach ($client->chatStream([
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $question],
                ]) as $delta) {
                    $answer .= $delta;
                    $this->emitSse('delta', ['text' => $delta]);
                }

                $result = [
                    'answer' => $answer,
                    'sources' => array_values($sourcesByMeeting),
                    'not_found' => false,
                    'top_score' => $topScore !== null ? round($topScore, 4) : null,
                    'model' => $client->chatModel(),
                    'latency_ms' => (int) round((hrtime(true) - $started) / 1_000_000),
                ];

                $this->emitSse('done', $result);
                $this->logQuestion($request->user()->id, $question, $result);
            } catch (OpenRouterException $e) {
                $this->emitSse('error', ['message' => $e->getMessage()]);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function emitSse(string $event, array $payload): void
    {
        echo 'event: '.$event."\n";
        echo 'data: '.json_encode($payload, JSON_UNESCAPED_UNICODE)."\n\n";

        if (function_exists('ob_flush')) {
            @ob_flush();
        }
        flush();
    }

    /**
     * @param  array{
     *   answer: string,
     *   sources: array<int, mixed>,
     *   not_found: bool,
     *   top_score?: ?float,
     *   model?: ?string,
     *   latency_ms?: int,
     * }  $result
     */
    protected function logQuestion(?int $userId, string $question, array $result): void
    {
        $attributes = [
            'user_id' => $userId,
            'question' => $question,
            'answer' => $result['answer'],
            'sources' => $result['sources'],
            'created_at' => now(),
        ];

        if (Schema::hasColumn('notulen_questions', 'model')) {
            $attributes['model'] = $result['model'] ?? null;
            $attributes['top_score'] = $result['top_score'] ?? null;
            $attributes['latency_ms'] = $result['latency_ms'] ?? null;
            $attributes['not_found'] = (bool) ($result['not_found'] ?? false);
        }

        NotulenQuestion::query()->create($attributes);
    }

    protected function openRouterError(OpenRouterException $e): JsonResponse
    {
        $status = $e->getStatusCode();
        if ($status < 400 || $status > 599) {
            $status = 503;
        }

        return response()->json([
            'message' => $e->getMessage(),
        ], $status);
    }
}
