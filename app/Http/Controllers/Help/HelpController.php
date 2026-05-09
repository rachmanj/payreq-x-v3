<?php

namespace App\Http\Controllers\Help;

use App\Exceptions\OpenRouterException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Help\HelpAskRequest;
use App\Http\Requests\Help\HelpFeedbackRequest;
use App\Models\HelpFeedback;
use App\Services\Help\HelpAssistantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;

class HelpController extends Controller
{
    public function ask(HelpAskRequest $request, HelpAssistantService $assistant): JsonResponse
    {
        try {
            $validated = $request->validated();
            $result = $assistant->ask(
                $validated['message'],
                $validated['locale'] ?? 'auto',
            );

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

    public function feedback(HelpFeedbackRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $feedback = HelpFeedback::query()->create([
            'user_id' => (int) $request->user()->id,
            'type' => $validated['type'],
            'title' => $validated['title'],
            'body' => $validated['body'],
            'steps_to_reproduce' => $validated['steps_to_reproduce'] ?? null,
        ]);

        $notify = config('help.feedback_notify_email');
        if (is_string($notify) && $notify !== '') {
            $lines = [
                'Type: '.$validated['type'],
                'User: '.$request->user()->name.' (#'.$request->user()->id.')',
                'Title: '.$validated['title'],
                '',
                $validated['body'],
            ];
            if (! empty($validated['steps_to_reproduce'])) {
                $lines[] = '';
                $lines[] = 'Steps:';
                $lines[] = $validated['steps_to_reproduce'];
            }

            Mail::raw(implode("\n", $lines), function ($message) use ($notify, $validated): void {
                $message->to($notify)->subject('[HELP feedback] '.$validated['type'].': '.$validated['title']);
            });
        }

        return response()->json([
            'message' => 'ok',
            'id' => $feedback->id,
        ], 201);
    }
}
