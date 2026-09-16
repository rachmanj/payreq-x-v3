<?php

namespace Tests\Unit;

use App\Services\SapService;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use ReflectionClass;
use Tests\TestCase;

class SapServiceFindReversalJournalNumberTest extends TestCase
{
    public function test_find_reversal_journal_number_uses_memo_contains_filter_and_returns_number(): void
    {
        $requestUri = null;
        $mock = new MockHandler([
            function ($request) use (&$requestUri) {
                $requestUri = (string) $request->getUri();

                return new Response(200, [], json_encode([
                    'value' => [
                        [
                            'Number' => 267672964,
                            'JdtNum' => 286718,
                            'Memo' => 'Verifikasi Journal 1 Periode Juli 2026(Reversal) - 286660',
                        ],
                    ],
                ]));
            },
        ]);

        $service = $this->makeSapServiceWithMockClient($mock);
        $result = $this->invokeFindReversalJournalNumber($service, '286660');

        $this->assertNotNull($requestUri);
        $decodedUri = urldecode($requestUri);
        $this->assertStringContainsString('contains(Memo,', $decodedUri);
        $this->assertStringContainsString("'(Reversal) - 286660'", $decodedUri);
        $this->assertSame('267672964', $result);
    }

    public function test_find_reversal_journal_number_returns_null_when_no_results(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['value' => []])),
        ]);

        $service = $this->makeSapServiceWithMockClient($mock);
        $result = $this->invokeFindReversalJournalNumber($service, '286660');

        $this->assertNull($result);
    }

    public function test_find_reversal_journal_number_returns_null_on_400_without_throwing(): void
    {
        $mock = new MockHandler([
            new Response(400, [], json_encode([
                'error' => [
                    'message' => [
                        'value' => 'Property StornoToTr of JournalEntry is invalid',
                    ],
                ],
            ])),
            new Response(200, [], json_encode(['value' => []])),
        ]);

        $service = $this->makeSapServiceWithMockClient($mock);
        $result = $this->invokeFindReversalJournalNumber($service, '286660');

        $this->assertNull($result);
    }

    public function test_find_reversal_journal_number_retries_without_select_when_select_query_fails(): void
    {
        $requestUris = [];
        $mock = new MockHandler([
            function ($request) use (&$requestUris) {
                $requestUri = (string) $request->getUri();
                $requestUris[] = $requestUri;
                if (str_contains(urldecode($requestUri), '$select')) {
                    return new Response(400, [], json_encode([
                        'error' => ['message' => ['value' => 'Bad $select']],
                    ]));
                }

                return new Response(200, [], json_encode([
                    'value' => [
                        [
                            'Number' => 267672964,
                            'JdtNum' => 286718,
                            'Memo' => 'Verifikasi Journal 1 Periode Juli 2026(Reversal) - 286660',
                        ],
                    ],
                ]));
            },
            function ($request) use (&$requestUris) {
                $requestUris[] = (string) $request->getUri();

                return new Response(200, [], json_encode([
                    'value' => [
                        [
                            'Number' => 267672964,
                            'JdtNum' => 286718,
                            'Memo' => 'Verifikasi Journal 1 Periode Juli 2026(Reversal) - 286660',
                        ],
                    ],
                ]));
            },
        ]);

        $service = $this->makeSapServiceWithMockClient($mock);
        $result = $this->invokeFindReversalJournalNumber($service, '286660');

        $this->assertCount(2, $requestUris);
        $this->assertStringContainsString('$select', urldecode($requestUris[0]));
        $this->assertStringNotContainsString('$select', urldecode($requestUris[1]));
        $this->assertSame('267672964', $result);
    }

    protected function invokeFindReversalJournalNumber(SapService $service, string $jdtNum): ?string
    {
        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('findReversalJournalNumber');
        $method->setAccessible(true);

        return $method->invoke($service, $jdtNum);
    }

    protected function makeSapServiceWithMockClient(MockHandler $mock): SapService
    {
        $handlerStack = HandlerStack::create($mock);
        $client = new Client([
            'handler' => $handlerStack,
            'base_uri' => 'https://sap.test/',
        ]);

        $service = new SapService;
        $reflection = new ReflectionClass($service);

        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($service, $client);

        $cookieJar = new CookieJar(false, [
            ['Name' => 'B1SESSION', 'Value' => 'test-session', 'Domain' => 'sap.test'],
        ]);

        $cookieJarProperty = $reflection->getProperty('cookieJar');
        $cookieJarProperty->setAccessible(true);
        $cookieJarProperty->setValue($service, $cookieJar);

        return $service;
    }
}
