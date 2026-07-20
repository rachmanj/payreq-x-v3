<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\OpenRouterException;
use App\Http\Controllers\Controller;
use App\Models\Meeting;
use App\Models\NotulenQuestion;
use App\Services\Notulen\AskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class NotulenApiController extends Controller
{
    public function ask(Request $request, AskService $askService): JsonResponse
    {
        $validated = $request->validate([
            'question' => ['required', 'string', 'max:4000'],
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
            $result = $askService->ask($validated['question'], signedDownloadUrls: true, filters: $filters);

            $attributes = [
                'user_id' => null,
                'question' => $validated['question'],
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

            return response()->json([
                'success' => true,
                'answer' => $result['answer'],
                'sources' => $result['sources'],
                'not_found' => $result['not_found'],
                'top_score' => $result['top_score'],
                'model' => $result['model'],
                'latency_ms' => $result['latency_ms'],
            ]);
        } catch (OpenRouterException $e) {
            $status = $e->getStatusCode();
            if ($status < 400 || $status > 599) {
                $status = 503;
            }

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $status);
        }
    }

    public function meetings(): JsonResponse
    {
        $meetings = Meeting::query()
            ->orderByDesc('meeting_date')
            ->orderByDesc('created_at')
            ->get(['id', 'title', 'meeting_date', 'status']);

        return response()->json([
            'success' => true,
            'data' => $meetings->map(fn (Meeting $m) => [
                'id' => $m->id,
                'title' => $m->title,
                'meeting_date' => $m->meeting_date?->format('Y-m-d'),
                'status' => $m->status,
            ]),
        ]);
    }
}
