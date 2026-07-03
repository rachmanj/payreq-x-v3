<?php

namespace App\Http\Controllers\Notulen;

use App\Exceptions\OpenRouterException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Notulen\AskAiQuestionRequest;
use App\Models\NotulenQuestion;
use App\Services\Notulen\AskService;
use Illuminate\Http\JsonResponse;

class AskController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:akses_notulen');
    }

    public function index()
    {
        return view('notulen.ask.index');
    }

    public function ask(AskAiQuestionRequest $request, AskService $askService): JsonResponse
    {
        try {
            $question = $request->validated('question');
            $result = $askService->ask($question);

            NotulenQuestion::query()->create([
                'user_id' => $request->user()->id,
                'question' => $question,
                'answer' => $result['answer'],
                'sources' => $result['sources'],
                'created_at' => now(),
            ]);

            return response()->json($result);
        } catch (OpenRouterException $e) {
            $status = $e->getStatusCode();
            if ($status < 400 || $status > 599) {
                $status = 503;
            }

            return response()->json([
                'message' => $e->getMessage(),
            ], $status);
        }
    }
}
