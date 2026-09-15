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

class SapServiceOutgoingPaymentTest extends TestCase
{
    public function test_outgoing_payment_entity_defaults_to_vendor_payments(): void
    {
        $this->assertSame('VendorPayments', (new SapService)->outgoingPaymentEntity());
    }

    public function test_outgoing_payment_entity_can_be_overridden_by_config(): void
    {
        config(['services.sap.outgoing_payment_entity' => 'Payments']);

        $this->assertSame('Payments', (new SapService)->outgoingPaymentEntity());
    }

    public function test_blank_outgoing_payment_entity_falls_back_to_vendor_payments(): void
    {
        config(['services.sap.outgoing_payment_entity' => '   ']);

        $this->assertSame('VendorPayments', (new SapService)->outgoingPaymentEntity());
    }

    public function test_get_vendor_payment_by_doc_entry_does_not_use_select(): void
    {
        $paymentBody = [
            'DocEntry' => 10616,
            'DocNum' => 88001,
            'DocDate' => '2026-09-07',
            'CardCode' => 'V001',
            'CardName' => 'BPJS KESEHATAN',
            'CashSum' => 0,
            'TransferSum' => 2000000,
            'TransferAccount' => '11102001',
            'DocCurrency' => 'IDR',
            'JournalRemarks' => 'Payment for Invoice BPJS-2026-09',
            'ProjectCode' => '000H',
        ];

        $requestUri = null;
        $mock = new MockHandler([
            function ($request) use ($paymentBody, &$requestUri) {
                $requestUri = (string) $request->getUri();
                if (str_contains($requestUri, '$select') || str_contains($requestUri, '%24select')) {
                    return new Response(400, [], json_encode(['error' => ['message' => 'Bad $select']]));
                }

                return new Response(200, [], json_encode($paymentBody));
            },
            new Response(204),
        ]);

        $service = $this->makeSapServiceWithMockClient($mock);

        $result = $service->getVendorPaymentByDocEntry(10616);

        $this->assertNotNull($requestUri);
        $this->assertStringContainsString('VendorPayments(10616)', $requestUri);
        $this->assertStringNotContainsString('$select', $requestUri);
        $this->assertStringNotContainsString('%24select', $requestUri);
        $this->assertNotNull($result);
        $this->assertSame('', $result['CheckBgNo']);
        $this->assertSame('BPJS KESEHATAN', $result['CardName']);
        $this->assertSame('11102001', $result['TransferAccount']);
    }

    public function test_get_vendor_payment_by_doc_entry_resolves_alternate_check_bg_property(): void
    {
        $paymentBody = [
            'DocEntry' => 10617,
            'DocNum' => 88002,
            'DocDate' => '2026-09-08',
            'CardName' => 'PT Vendor BG',
            'TransferSum' => 500000,
            'TransferAccount' => '11102002',
            'DocCurrency' => 'IDR',
            'JournalRemarks' => 'BG payment',
            'ProjectCode' => '022C',
            'BankersGuaranteeNo' => 'BG-2026-001',
        ];

        $mock = new MockHandler([
            new Response(200, [], json_encode($paymentBody)),
            new Response(204),
        ]);

        $service = $this->makeSapServiceWithMockClient($mock);

        $result = $service->getVendorPaymentByDocEntry(10617);

        $this->assertNotNull($result);
        $this->assertSame('BG-2026-001', $result['CheckBgNo']);
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

        $isLoggedInProperty = $reflection->getProperty('isLoggedIn');
        $isLoggedInProperty->setAccessible(true);
        $isLoggedInProperty->setValue($service, true);

        return $service;
    }
}
