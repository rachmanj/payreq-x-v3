<?php

namespace App\Services\Help;

use App\Exceptions\OpenRouterException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class HelpOpenRouterClient
{
    protected const MAX_EMBEDDING_INPUT_CHARS = 30000;

    public function __construct(
        protected ?string $apiKey = null,
        protected ?string $baseUrl = null,
        protected ?string $embeddingModel = null,
        protected ?string $helpModel = null,
        protected ?int $timeout = null,
        protected ?int $connectTimeout = null,
        protected ?int $embeddingRetries = null,
    ) {
        $this->apiKey = $apiKey ?? config('services.openrouter.api_key');
        $this->baseUrl = rtrim($baseUrl ?? config('services.openrouter.base_url', 'https://openrouter.ai/api/v1'), '/');
        $this->embeddingModel = $embeddingModel ?? config('services.openrouter.embedding_model');
        $this->helpModel = $helpModel ?? config('services.openrouter.help_model');
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
        if (blank($this->apiKey)) {
            throw new OpenRouterException('OpenRouter is not configured.', 500);
        }

        $truncated = array_map(function (string $text): string {
            if (strlen($text) <= self::MAX_EMBEDDING_INPUT_CHARS) {
                return $text;
            }

            return substr($text, 0, self::MAX_EMBEDDING_INPUT_CHARS);
        }, $inputs);

        $attempts = 0;
        $lastException = null;

        while ($attempts <= $this->embeddingRetries) {
            try {
                $response = $this->pendingRequest()->post($this->baseUrl.'/embeddings', [
                    'model' => $this->embeddingModel,
                    'input' => $truncated,
                ]);

                if ($response->successful()) {
                    return $this->parseEmbeddingsResponse($response->json());
                }

                $payload = $response->json();

                throw new OpenRouterException(
                    data_get($payload, 'error.message', 'OpenRouter embeddings request failed.'),
                    $response->status(),
                    is_array($payload) ? $payload : null
                );
            } catch (ConnectionException $exception) {
                $lastException = $exception;
                $attempts++;
            }
        }

        throw new OpenRouterException('Unable to reach OpenRouter embeddings API.', 503, null, $lastException);
    }

    /**
     * @param  array<int, array<string, mixed>>  $messages
     */
    public function helpChat(array $messages, ?string $model = null): string
    {
        if (blank($this->apiKey)) {
            throw new OpenRouterException('OpenRouter is not configured.', 500);
        }

        try {
            $response = $this->pendingRequest()->post($this->baseUrl.'/chat/completions', [
                'model' => $model ?? $this->helpModel,
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

    protected function pendingRequest()
    {
        return Http::acceptJson()
            ->connectTimeout($this->connectTimeout)
            ->timeout($this->timeout)
            ->withHeaders([
                'Authorization' => 'Bearer '.$this->apiKey,
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
