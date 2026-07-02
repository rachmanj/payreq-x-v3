<?php

namespace Tests\Unit;

use App\Exceptions\OpenRouterException;
use App\Services\OpenRouterService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenRouterBankStatementTest extends TestCase
{
    public function test_extract_bank_statement_uses_bank_statement_model(): void
    {
        config([
            'services.openrouter.api_key' => 'test-key',
            'services.openrouter.base_url' => 'https://openrouter.ai/api/v1',
            'services.openrouter.model' => 'openai/gpt-4o',
            'services.openrouter.bank_statement_model' => 'google/gemini-3-flash-preview',
        ]);

        Http::fake([
            'https://openrouter.ai/api/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => '{"opening_balance":100,"closing_balance":200,"lines":[]}',
                        ],
                    ],
                ],
            ]),
        ]);

        $service = app(OpenRouterService::class);
        $payload = $service->extractBankStatementFromPdfBase64(base64_encode('%PDF-1.4 test'));

        $this->assertEquals(100, $payload['opening_balance']);
        $this->assertEquals(200, $payload['closing_balance']);

        Http::assertSent(function ($request): bool {
            return $request['model'] === 'google/gemini-3-flash-preview';
        });
    }

    public function test_provider_error_message_is_unwrapped_from_metadata(): void
    {
        config([
            'services.openrouter.api_key' => 'test-key',
            'services.openrouter.base_url' => 'https://openrouter.ai/api/v1',
            'services.openrouter.bank_statement_model' => 'openai/gpt-4o',
        ]);

        Http::fake([
            'https://openrouter.ai/api/v1/chat/completions' => Http::response([
                'error' => [
                    'message' => 'Provider returned error',
                    'metadata' => [
                        'raw' => json_encode([
                            'error' => [
                                'message' => 'Invalid MIME type. Only image types are supported.',
                            ],
                        ]),
                    ],
                ],
            ], 400),
        ]);

        $this->expectException(OpenRouterException::class);
        $this->expectExceptionMessage('Invalid MIME type. Only image types are supported.');

        app(OpenRouterService::class)->extractBankStatementFromPdfBase64(base64_encode('%PDF-1.4 test'));
    }
}
