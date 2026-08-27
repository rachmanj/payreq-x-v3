<?php

namespace Tests\Unit;

use App\Services\SapService;
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
}
