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
use Illuminate\Http\Request;
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

    public function history(Request $request): JsonResponse
    {
        $items = NotulenQuestion::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get(['id', 'question', 'created_at', 'not_found']);

        return response()->json([
            'items' => $items->map(static fn (NotulenQuestion $row) => [
                'id' => $row->id,
                'question' => $row->question,
                'created_at' => $row->created_at?->toIso8601String(),
                'not_found' => (bool) $row->not_found,
            ])->values(),
        ]);
    }

    public function historyShow(Request $request, int $id): JsonResponse
    {
        $row = NotulenQuestion::query()
            ->where('user_id', $request->user()->id)
            ->where('id', $id)
            ->firstOrFail(['question', 'answer', 'sources', 'model', 'top_score', 'latency_ms', 'not_found']);

        return response()->json([
            'question' => $row->question,
            'answer' => $row->answer,
            'sources' => $row->sources ?? [],
            'model' => $row->model,
            'top_score' => $row->top_score,
            'latency_ms' => $row->latency_ms,
            'not_found' => (bool) $row->not_found,
        ]);
    }

    public function suggestions(Request $request, AskService $askService): JsonResponse
    {
        $validated = $request->validate([
            'question' => ['required', 'string', 'max:4000'],
            'answer' => ['required', 'string', 'max:20000'],
            'meeting_ids' => ['sometimes', 'array'],
            'meeting_ids.*' => ['integer', 'exists:meetings,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $filters = [];
        if (! empty($validated['meeting_ids'])) {
            $filters['meeting_ids'] = array_map('intval', $validated['meeting_ids']);
        }
        if (! empty($validated['date_from'])) {
            $filters['date_from'] = $validated['date_from'];
        }
        if (! empty($validated['date_to'])) {
            $filters['date_to'] = $validated['date_to'];
        }

        try {
            $items = $askService->suggestions(
                $validated['question'],
                $validated['answer'],
                $filters
            );

            return response()->json(['suggestions' => $items]);
        } catch (OpenRouterException $e) {
            return $this->openRouterError($e);
        }
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

                $built = $askService->buildRetrievalContext($picked);

                $this->emitSse('meta', [
                    'sources' => $built['sources'],
                    'top_score' => $built['top_score'],
                    'model' => $client->chatModel(),
                    'not_found' => false,
                ]);

                $answer = '';
                foreach ($client->chatStream([
                    ['role' => 'system', 'content' => $built['system_prompt']],
                    ['role' => 'user', 'content' => $question],
                ]) as $delta) {
                    $answer .= $delta;
                    $this->emitSse('delta', ['text' => $delta]);
                }

                $result = [
                    'answer' => $answer,
                    'sources' => $built['sources'],
                    'not_found' => false,
                    'top_score' => $built['top_score'],
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
