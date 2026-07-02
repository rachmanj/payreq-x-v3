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
        protected ?string $bankStatementModel = null,
        protected ?int $timeout = null,
    ) {
        $this->apiKey = $apiKey ?? config('services.openrouter.api_key');
        $this->baseUrl = rtrim($baseUrl ?? config('services.openrouter.base_url', 'https://openrouter.ai/api/v1'), '/');
        $this->defaultModel = $defaultModel ?? config('services.openrouter.model', 'google/gemini-2.0-flash-001');
        $this->bankStatementModel = $bankStatementModel ?? config('services.openrouter.bank_statement_model', 'google/gemini-3-flash-preview');
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
            $this->resolveApiErrorMessage(is_array($payload) ? $payload : null, 'OpenRouter request failed.'),
            $response->status(),
            is_array($payload) ? $payload : null
        );
    }

    protected function resolveApiErrorMessage(?array $payload, string $fallback): string
    {
        $message = (string) data_get($payload, 'error.message', $fallback);

        if ($message !== 'Provider returned error') {
            return $message;
        }

        $raw = data_get($payload, 'error.metadata.raw');
        if (! is_string($raw) || $raw === '') {
            return $message;
        }

        $nested = json_decode($raw, true);
        $nestedMessage = data_get($nested, 'error.message');

        return is_string($nestedMessage) && $nestedMessage !== '' ? $nestedMessage : $message;
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

        $json = $this->chat($messages, $this->bankStatementModel);
        $content = data_get($json, 'choices.0.message.content');
        if (! is_string($content)) {
            throw new OpenRouterException('Invalid OpenRouter response: missing message content.', 500, is_array($json) ? $json : null);
        }

        return $this->decodeJsonPayload($content);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function extractReceiptFromImageBase64(string $base64Image, string $mimeType = 'image/jpeg'): array
    {
        $prompt = <<<'PROMPT'
You are parsing a photo that may contain ONE or MORE Indonesian Pertamina SPBU fuel receipts laid out together.
Identify every individual receipt visible in the image (each separate slip is one receipt).
Return ONLY valid JSON (no markdown fences) with this exact shape:
{"receipts":[{"description":string,"amount":number,"expense_date":"Y-m-d"|null,"km_position":number|null,"qty":number|null,"unit_no":string|null,"nopol":string|null,"type":"fuel","uom":"liter","confidence":number},...]}

Rules (apply to each receipt object):
- description: if fuel grade (e.g. Pertamax, Dexlite) AND SPBU station number are readable, use "BBM [Grade] - SPBU [No]". Otherwise use exactly "Fuel Kendaraan".
- amount: total transaction amount in Rupiah (integer, no separators).
- expense_date: transaction date as Y-m-d.
- km_position: odometer/HM reading as integer only (strip "KM" prefix). null if absent.
- qty: fuel volume in liters as number. null if absent.
- unit_no: look for HANDWRITTEN text matching two uppercase letters, one space, three digits (e.g. "VA 057", "VA 083"). Return exactly as written including the space. null if not found.
- nopol: printed vehicle plate if present. null if "Not Entered", blank, or unreadable.
- type: always "fuel".
- uom: always "liter".
- confidence: 0-1 estimate per receipt.
- Include one object in "receipts" for every distinct receipt visible. Do not merge multiple receipts into one object.
PROMPT;

        $messages = [
            [
                'role' => 'user',
                'content' => [
                    ['type' => 'text', 'text' => $prompt],
                    [
                        'type' => 'image_url',
                        'image_url' => [
                            'url' => 'data:'.$mimeType.';base64,'.$base64Image,
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

        return $this->decodeReceiptJsonPayload($content);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function decodeReceiptJsonPayload(string $content): array
    {
        $trimmed = trim($content);
        if (preg_match('/^```(?:json)?\s*([\s\S]*?)\s*```$/m', $trimmed, $matches)) {
            $trimmed = trim($matches[1]);
        }

        $decoded = json_decode($trimmed, true);
        if (! is_array($decoded)) {
            throw new OpenRouterException('AI returned invalid JSON.', 500);
        }

        if (isset($decoded['receipts']) && is_array($decoded['receipts'])) {
            return array_values($decoded['receipts']);
        }

        if (isset($decoded['description']) || isset($decoded['amount'])) {
            return [$decoded];
        }

        throw new OpenRouterException('AI returned invalid JSON: missing receipts array.', 500);
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
