<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class DebugInvoicePaymentRequestsTempTest extends TestCase
{
    public function test_invoice_payment_uri_includes_x_response_source_app_header(): void
    {
        Route::middleware('web')->get('/__test/cashier/invoice-payment/ping', function () {
            return response()->json(['ok' => true]);
        });

        $response = $this->getJson('/__test/cashier/invoice-payment/ping');

        $response->assertOk();
        $response->assertHeader('X-Response-Source', 'app');
    }

    public function test_non_invoice_payment_uri_does_not_include_x_response_source_header(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $this->assertNull($response->headers->get('X-Response-Source'));
    }
}
