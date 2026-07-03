<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\OpenRouterException;
use App\Http\Controllers\Controller;
use App\Models\Meeting;
use App\Models\NotulenQuestion;
use App\Services\Notulen\AskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotulenApiController extends Controller
{
    public function ask(Request $request, AskService $askService): JsonResponse
    {
        $validated = $request->validate([
            'question' => ['required', 'string', 'max:4000'],
        ]);

        try {
            $result = $askService->ask($validated['question'], signedDownloadUrls: true);

            NotulenQuestion::query()->create([
                'user_id' => null,
                'question' => $validated['question'],
                'answer' => $result['answer'],
                'sources' => $result['sources'],
                'created_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'answer' => $result['answer'],
                'sources' => $result['sources'],
                'not_found' => $result['not_found'],
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
