<?php

namespace App\Services\Notulen;

use App\Exceptions\OpenRouterException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class NotulenOpenRouterClient
{
    protected const MAX_EMBEDDING_INPUT_CHARS = 30000;

    public function __construct(
        protected ?string $apiKey = null,
        protected ?string $baseUrl = null,
        protected ?string $embeddingModel = null,
        protected ?string $notulenModel = null,
        protected ?string $notulenOcrModel = null,
        protected ?int $timeout = null,
        protected ?int $connectTimeout = null,
        protected ?int $embeddingRetries = null,
    ) {
        $this->apiKey = $apiKey ?? config('services.openrouter.api_key');
        $this->baseUrl = rtrim($baseUrl ?? config('services.openrouter.base_url', 'https://openrouter.ai/api/v1'), '/');
        $this->embeddingModel = $embeddingModel ?? config('services.openrouter.embedding_model');
        $this->notulenModel = $notulenModel ?? config('services.openrouter.notulen_model');
        $this->notulenOcrModel = $notulenOcrModel ?? config('services.openrouter.notulen_ocr_model');
        $this->timeout = $timeout ?? (int) config('services.openrouter.timeout', 120);
        $this->connectTimeout = $connectTimeout ?? (int) config('services.openrouter.connect_timeout', 10);
        $this->embeddingRetries = $embeddingRetries ?? (int) config('services.openrouter.embedding_retries', 2);
    }

    /**
     * @return array<int, float>
     */
    public function embed(string $input): array
    {
        $vectors = $this->embedMany([$input]);

        return $vectors[0];
    }

    /**
     * @param  array<int, string>  $inputs
     * @return array<int, array<int, float>>
     */
    public function embedMany(array $inputs): array
    {
        [$apiKey, $baseUrl, $model] = $this->embeddingEndpoint();

        if (blank($apiKey)) {
            throw new OpenRouterException('Embeddings provider is not configured.', 500);
        }

        $truncated = array_map(function (string $text): string {
            if (mb_strlen($text, 'UTF-8') <= self::MAX_EMBEDDING_INPUT_CHARS) {
                return $text;
            }

            return mb_substr($text, 0, self::MAX_EMBEDDING_INPUT_CHARS, 'UTF-8');
        }, $inputs);

        $attempts = 0;
        $lastException = null;

        while ($attempts <= $this->embeddingRetries) {
            try {
                $response = $this->pendingRequest($apiKey)->post($baseUrl.'/embeddings', [
                    'model' => $model,
                    'input' => $truncated,
                ]);

                if ($response->successful()) {
                    return $this->parseEmbeddingsResponse($response->json());
                }

                $payload = $response->json();

                throw new OpenRouterException(
                    data_get($payload, 'error.message', 'Embeddings request failed.'),
                    $response->status(),
                    is_array($payload) ? $payload : null
                );
            } catch (ConnectionException $exception) {
                $lastException = $exception;
                $attempts++;
            }
        }

        throw new OpenRouterException('Unable to reach embeddings API.', 503, null, $lastException);
    }

    /**
     * @return array{0: string|null, 1: string, 2: string|null}
     */
    protected function embeddingEndpoint(): array
    {
        $provider = strtolower((string) config('services.openrouter.embedding_provider', 'openrouter'));

        if ($provider === 'openai') {
            return [
                config('services.openai.api_key') ?: $this->apiKey,
                rtrim((string) config('services.openai.base_url', 'https://api.openai.com/v1'), '/'),
                config('services.openai.embedding_model', 'text-embedding-3-small'),
            ];
        }

        return [
            $this->apiKey,
            $this->baseUrl,
            $this->embeddingModel,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $messages
     */
    public function chat(array $messages, ?string $model = null): string
    {
        if (blank($this->apiKey)) {
            throw new OpenRouterException('OpenRouter is not configured.', 500);
        }

        try {
            $response = $this->pendingRequest()->post($this->baseUrl.'/chat/completions', [
                'model' => $model ?? $this->notulenModel,
                'messages' => $messages,
            ]);
        } catch (ConnectionException $exception) {
            throw new OpenRouterException('Unable to reach OpenRouter.', 503, null, $exception);
        }

        if ($response->successful()) {
            $content = data_get($response->json(), 'choices.0.message.content');
            if (! is_string($content)) {
                throw new OpenRouterException('Invalid OpenRouter response: missing message content.', 500, $response->json());
            }

            return $content;
        }

        $payload = $response->json();

        throw new OpenRouterException(
            data_get($payload, 'error.message', 'OpenRouter request failed.'),
            $response->status(),
            is_array($payload) ? $payload : null
        );
    }

    public function chatModel(): ?string
    {
        return $this->notulenModel;
    }

    /**
     * Yields incremental text deltas from OpenRouter streaming chat.
     *
     * @param  array<int, array<string, mixed>>  $messages
     * @return \Generator<int, string>
     */
    public function chatStream(array $messages, ?string $model = null): \Generator
    {
        if (blank($this->apiKey)) {
            throw new OpenRouterException('OpenRouter is not configured.', 500);
        }

        try {
            $response = $this->pendingRequest()
                ->withOptions(['stream' => true])
                ->post($this->baseUrl.'/chat/completions', [
                    'model' => $model ?? $this->notulenModel,
                    'messages' => $messages,
                    'stream' => true,
                ]);
        } catch (ConnectionException $exception) {
            throw new OpenRouterException('Unable to reach OpenRouter.', 503, null, $exception);
        }

        if (! $response->successful()) {
            $payload = $response->json();

            throw new OpenRouterException(
                data_get($payload, 'error.message', 'OpenRouter stream request failed.'),
                $response->status(),
                is_array($payload) ? $payload : null
            );
        }

        $body = $response->toPsrResponse()->getBody();
        $buffer = '';

        while (! $body->eof()) {
            $buffer .= $body->read(1024);

            while (($pos = strpos($buffer, "\n")) !== false) {
                $line = trim(substr($buffer, 0, $pos));
                $buffer = substr($buffer, $pos + 1);

                if ($line === '' || ! str_starts_with($line, 'data:')) {
                    continue;
                }

                $data = trim(substr($line, 5));
                if ($data === '[DONE]') {
                    return;
                }

                $json = json_decode($data, true);
                if (! is_array($json)) {
                    continue;
                }

                $delta = data_get($json, 'choices.0.delta.content');
                if (is_string($delta) && $delta !== '') {
                    yield $delta;
                }
            }
        }
    }

    public function extractTextFromPdfBase64(string $base64Pdf): string
    {
        $prompt = <<<'PROMPT'
You are extracting text from a meeting minutes (notulen rapat) PDF document.
The PDF may be scanned or image-based. Read every page carefully.
Return ONLY the extracted plain text: preserve paragraphs, numbered lists, headings, dates, and attendee names.
Do not summarize. Do not add commentary. Do not use markdown fences.
PROMPT;

        return trim($this->chat([
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
        ], $this->notulenOcrModel));
    }

    protected function pendingRequest(?string $apiKey = null)
    {
        return Http::acceptJson()
            ->connectTimeout($this->connectTimeout)
            ->timeout($this->timeout)
            ->withHeaders([
                'Authorization' => 'Bearer '.($apiKey ?? $this->apiKey),
                'HTTP-Referer' => config('app.url'),
                'X-Title' => config('app.name'),
            ]);
    }

    /**
     * @param  array<string, mixed>|null  $payload
     * @return array<int, array<int, float>>
     */
    protected function parseEmbeddingsResponse(?array $payload): array
    {
        if (! is_array($payload)) {
            throw new OpenRouterException('Invalid OpenRouter embeddings response.', 500);
        }

        $data = $payload['data'] ?? null;
        if (! is_array($data) || $data === []) {
            throw new OpenRouterException('Invalid OpenRouter embeddings response: missing data.', 500, $payload);
        }

        usort($data, fn ($a, $b) => ($a['index'] ?? 0) <=> ($b['index'] ?? 0));

        $out = [];
        foreach ($data as $row) {
            $embedding = $row['embedding'] ?? null;
            if (! is_array($embedding)) {
                throw new OpenRouterException('Invalid OpenRouter embeddings response: missing vector.', 500, $payload);
            }

            $out[] = array_map(static fn ($v) => (float) $v, $embedding);
        }

        return $out;
    }
}
