<?php

namespace Tests\Feature;

use App\Models\SapBusinessPartner;
use App\Models\User;
use App\Services\SapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class InvoicePaymentCreditMemoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'pay_invoice_with_credit_memo', 'guard_name' => 'web']);
    }

    public function test_credit_memos_endpoint_requires_permission(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson(route('cashier.invoice-payment.credit-memos', [
                'invoice_id' => '1',
                'supplier_sap_code' => 'VSUP01',
            ]))
            ->assertForbidden();
    }

    public function test_credit_memos_endpoint_returns_open_credit_notes_for_vendor(): void
    {
        SapBusinessPartner::query()->create([
            'code' => 'VSUP01',
            'name' => 'Vendor',
            'type' => SapBusinessPartner::TYPE_SUPPLIER,
            'active' => true,
        ]);

        $this->mock(SapService::class, function ($mock): void {
            $mock->shouldReceive('listOpenPurchaseCreditNotesForVendor')
                ->once()
                ->with('VSUP01')
                ->andReturn([
                    [
                        'DocEntry' => 322,
                        'DocNum' => 257100050,
                        'DocDate' => '2026-03-01T00:00:00Z',
                        'DocTotal' => 1000000,
                        'PaidToDate' => 50000,
                        'NumAtCard' => 'INV-1 (CM 123)',
                    ],
                ]);
        });

        $user = User::factory()->create();
        $user->givePermissionTo('pay_invoice_with_credit_memo');

        $this->actingAs($user)
            ->getJson(route('cashier.invoice-payment.credit-memos', [
                'invoice_id' => '1',
                'supplier_sap_code' => 'VSUP01',
            ]))
            ->assertOk()
            ->assertJsonPath('credit_memos.0.doc_entry', 322)
            ->assertJsonPath('credit_memos.0.remaining_balance', 950000);
    }
}
