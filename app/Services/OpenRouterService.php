<?php

namespace App\Services;

use App\Exceptions\OpenRouterException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class OpenRouterService
{
    public function __construct(
        protected ?string $apiKey = null,
        protected ?string $baseUrl = null,
        protected ?string $defaultModel = null,
        protected ?int $timeout = null,
    ) {
        $this->apiKey = $apiKey ?? config('services.openrouter.api_key');
        $this->baseUrl = rtrim($baseUrl ?? config('services.openrouter.base_url', 'https://openrouter.ai/api/v1'), '/');
        $this->defaultModel = $defaultModel ?? config('services.openrouter.model', 'google/gemini-2.0-flash-001');
        $this->timeout = $timeout ?? (int) config('services.openrouter.timeout', 120);
    }

    /**
     * @param  array<int, array<string, mixed>>  $messages
     * @return array<string, mixed>
     */
    public function chat(array $messages, ?string $model = null): array
    {
        if (blank($this->apiKey)) {
            throw new OpenRouterException('OpenRouter is not configured.', 500);
        }

        try {
            $response = Http::acceptJson()
                ->timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => 'Bearer '.$this->apiKey,
                    'HTTP-Referer' => config('app.url'),
                    'X-Title' => config('app.name'),
                ])
                ->post("{$this->baseUrl}/chat/completions", [
                    'model' => $model ?? $this->defaultModel,
                    'messages' => $messages,
                ]);
        } catch (ConnectionException $exception) {
            throw new OpenRouterException('Unable to reach OpenRouter.', 503, null, $exception);
        }

        if ($response->successful()) {
            return $response->json();
        }

        $payload = $response->json();

        throw new OpenRouterException(
            data_get($payload, 'error.message', 'OpenRouter request failed.'),
            $response->status(),
            is_array($payload) ? $payload : null
        );
    }

    /**
     * @return array{opening_balance: float|null, closing_balance: float|null, lines: array<int, array<string, mixed>>}
     */
    public function extractBankStatementFromPdfBase64(string $base64Pdf): array
    {
        $prompt = <<<'PROMPT'
You are parsing an Indonesian bank account statement PDF.
Return ONLY valid JSON (no markdown fences) with this shape:
{"opening_balance":number|null,"closing_balance":number|null,"lines":[{"transaction_date":"Y-m-d","value_date":"Y-m-d"|null,"description":string,"reference":string|null,"debit":number,"credit":number,"balance":number|null,"confidence":number}]}
Rules: Use 0 for debit or credit when empty. Dates must be Y-m-d or null. confidence is 0-1 per line estimate.
PROMPT;

        $messages = [
            [
                'role' => 'user',
                'content' => [
                    ['type' => 'text', 'text' => $prompt],
                    [
                        'type' => 'image_url',
                        'image_url' => [
                            'url' => 'data:application/pdf;base64,'.$base64Pdf,
                        ],
                    ],
                ],
            ],
        ];

        $json = $this->chat($messages);
        $content = data_get($json, 'choices.0.message.content');
        if (! is_string($content)) {
            throw new OpenRouterException('Invalid OpenRouter response: missing message content.', 500, is_array($json) ? $json : null);
        }

        return $this->decodeJsonPayload($content);
    }

    /**
     * @return array{opening_balance: float|null, closing_balance: float|null, lines: array<int, array<string, mixed>>}
     */
    protected function decodeJsonPayload(string $content): array
    {
        $trimmed = trim($content);
        if (preg_match('/^```(?:json)?\s*([\s\S]*?)\s*```$/m', $trimmed, $matches)) {
            $trimmed = trim($matches[1]);
        }

        $decoded = json_decode($trimmed, true);
        if (! is_array($decoded)) {
            throw new OpenRouterException('AI returned invalid JSON.', 500);
        }

        if (! isset($decoded['lines']) || ! is_array($decoded['lines'])) {
            $decoded['lines'] = [];
        }

        return $decoded;
    }
}
