<?php

namespace Tests\Feature;

use App\Models\BpjsApInvoice;
use App\Models\Project;
use App\Models\SapBusinessPartner;
use App\Models\User;
use App\Services\SapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class BpjsApInvoiceSapStatusTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'akses_ap_invoice_bpjs', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'submit_sap_ap_invoice_bpjs', 'guard_name' => 'web']);

        SapBusinessPartner::query()->create([
            'code' => 'VBPKEIDR01',
            'name' => 'BPJS KESEHATAN',
            'type' => SapBusinessPartner::TYPE_SUPPLIER,
            'active' => true,
        ]);

        Project::query()->create([
            'code' => '000H',
            'name' => 'Head Office',
            'is_active' => true,
            'is_selectable' => true,
        ]);
    }

    public function test_data_endpoint_shows_sap_status_chips(): void
    {
        $user = $this->authorizedUser();

        $pending = BpjsApInvoice::factory()->create([
            'sap_doc_entry' => null,
            'sap_doc_num' => null,
        ]);

        $open = BpjsApInvoice::factory()->posted()->create([
            'periode' => '2026-08',
            'sap_document_status' => 'bost_Open',
            'sap_cancelled' => false,
            'sap_status_synced_at' => now(),
        ]);

        $cancelledInSap = BpjsApInvoice::factory()->posted()->create([
            'periode' => '2026-07',
            'status' => BpjsApInvoice::STATUS_POSTED,
            'sap_document_status' => 'bost_Open',
            'sap_cancelled' => true,
            'sap_status_synced_at' => now(),
        ]);

        $this->mock(SapService::class, function ($mock) {
            $mock->shouldReceive('getPurchaseInvoiceStatus')->andReturn(null);
        });

        $response = $this->actingAs($user)
            ->getJson(route('bpjs-ap-invoices.data'));

        $response->assertOk();

        $records = collect($response->json('data'));

        $this->assertStringContainsString(
            'Belum diposting ke SAP',
            (string) $records->firstWhere('id', $pending->id)['sap_status']
        );
        $this->assertStringContainsString(
            'Open',
            (string) $records->firstWhere('id', $open->id)['sap_status']
        );
        $this->assertStringContainsString(
            'Cancelled in SAP',
            (string) $records->firstWhere('id', $cancelledInSap->id)['sap_status']
        );
    }

    public function test_refresh_persists_sap_status_fields(): void
    {
        $invoice = BpjsApInvoice::factory()->posted()->create([
            'sap_document_status' => 'bost_Open',
            'sap_cancelled' => false,
            'sap_status_synced_at' => now()->subDay(),
        ]);

        $this->mock(SapService::class, function ($mock) {
            $mock->shouldReceive('getPurchaseInvoiceStatus')
                ->once()
                ->andReturn([
                    'DocEntry' => 123,
                    'DocNum' => 456,
                    'DocumentStatus' => 'bost_Close',
                    'Cancelled' => 'tNO',
                ]);
        });

        $result = app(\App\Services\BpjsApInvoiceSapStatusService::class)->refresh($invoice);

        $this->assertNotNull($result);
        $invoice->refresh();
        $this->assertSame('bost_Close', $invoice->sap_document_status);
        $this->assertFalse($invoice->sap_cancelled);
        $this->assertNotNull($invoice->sap_status_synced_at);
    }

    public function test_refresh_failure_preserves_previous_sap_status(): void
    {
        $syncedAt = now()->subDays(2);
        $invoice = BpjsApInvoice::factory()->posted()->create([
            'sap_document_status' => 'bost_Open',
            'sap_cancelled' => false,
            'sap_status_synced_at' => $syncedAt,
        ]);

        $this->mock(SapService::class, function ($mock) {
            $mock->shouldReceive('getPurchaseInvoiceStatus')
                ->once()
                ->andThrow(new \RuntimeException('SAP unavailable'));
        });

        $result = app(\App\Services\BpjsApInvoiceSapStatusService::class)->refresh($invoice);

        $this->assertNull($result);
        $invoice->refresh();
        $this->assertSame('bost_Open', $invoice->sap_document_status);
        $this->assertFalse($invoice->sap_cancelled);
        $this->assertSame(
            $syncedAt->toDateTimeString(),
            $invoice->sap_status_synced_at?->toDateTimeString()
        );
    }

    public function test_sync_sap_status_all_updates_rows_and_returns_summary(): void
    {
        $user = $this->authorizedUser();

        BpjsApInvoice::factory()->posted()->create(['periode' => '2026-05']);
        BpjsApInvoice::factory()->posted()->create(['periode' => '2026-06']);

        $this->mock(SapService::class, function ($mock) {
            $mock->shouldReceive('getPurchaseInvoiceStatus')
                ->twice()
                ->andReturn([
                    'DocEntry' => 1,
                    'DocNum' => 2,
                    'DocumentStatus' => 'bost_Open',
                    'Cancelled' => 'tNO',
                ]);
        });

        $this->actingAs($user)
            ->post(route('bpjs-ap-invoices.sync-sap-status-all'))
            ->assertRedirect()
            ->assertSessionHas('success', 'Status SAP diperbarui: 2 baris, gagal 0 baris.');
    }

    public function test_data_endpoint_returns_ok_when_sap_status_refresh_throws(): void
    {
        $user = $this->authorizedUser();

        BpjsApInvoice::factory()->posted()->create([
            'sap_status_synced_at' => null,
        ]);

        $this->mock(\App\Services\BpjsApInvoiceSapStatusService::class, function ($mock) {
            $mock->shouldReceive('refreshStale')
                ->once()
                ->andThrow(new \RuntimeException('SAP down'));
        });

        $this->actingAs($user)
            ->getJson(route('bpjs-ap-invoices.data'))
            ->assertOk();
    }

    public function test_invoice_payment_preview_includes_cancelled_flag_for_bpjs(): void
    {
        SapBusinessPartner::query()->create([
            'code' => 'VBPTKIDR01',
            'name' => 'BPJS KETENAGAKERJAAN',
            'type' => SapBusinessPartner::TYPE_SUPPLIER,
            'active' => true,
        ]);

        $bpjs = BpjsApInvoice::factory()->posted()->create([
            'jenis' => BpjsApInvoice::JENIS_KETENAGAKERJAAN,
            'amount' => 2000000,
            'paid_amount' => 0,
        ]);

        $this->mock(SapService::class, function ($mock) use ($bpjs) {
            $mock->shouldReceive('getPurchaseInvoiceByDocEntry')
                ->andReturn([
                    'DocEntry' => $bpjs->sap_doc_entry,
                    'DocNum' => $bpjs->sap_doc_num,
                    'DocTotal' => 2000000,
                    'PaidToDate' => 2000000,
                    'DocumentStatus' => 'bost_Open',
                    'Cancelled' => 'tYES',
                    'CardCode' => 'VBPTKIDR01',
                ]);
        });

        $user = User::factory()->create();
        $user->givePermissionTo('submit_sap_invoice_payment');

        $this->actingAs($user)
            ->getJson(route('cashier.invoice-payment.sap-payment.preview', [
                'invoiceId' => 'bpjs:'.$bpjs->id,
                'invoice_number' => $bpjs->invoiceNumber(),
                'supplier_sap_code' => $bpjs->supplierSapCode(),
                'amount' => 2000000,
            ]))
            ->assertOk()
            ->assertJsonPath('fully_paid', true)
            ->assertJsonPath('preview.ap_invoice.cancelled', true)
            ->assertJsonPath('preview.ap_invoice.document_status', 'bost_Open');
    }

    private function authorizedUser(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['akses_ap_invoice_bpjs', 'submit_sap_ap_invoice_bpjs']);

        return $user;
    }
}
