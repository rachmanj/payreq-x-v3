<?php

namespace Tests\Feature;

use App\Models\BpjsApInvoice;
use App\Models\Project;
use App\Models\SapBusinessPartner;
use App\Models\SapSubmissionLog;
use App\Models\User;
use App\Services\SapBpjsApInvoiceBuilder;
use App\Services\SapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class BpjsApInvoiceRepostSapTest extends TestCase
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

    public function test_data_endpoint_shows_repost_button_only_when_sap_cancelled(): void
    {
        $user = $this->authorizedUser();

        $cancelledInSap = $this->postedCancelledInSapInvoice([
            'periode' => '2026-07',
            'sap_doc_num' => '267007588',
        ]);

        $openInSap = BpjsApInvoice::factory()->posted()->create([
            'periode' => '2026-08',
            'sap_cancelled' => false,
            'sap_document_status' => 'bost_Open',
        ]);

        $paidPartial = BpjsApInvoice::factory()->posted()->create([
            'periode' => '2026-06',
            'sap_cancelled' => true,
            'paid_amount' => 1000,
        ]);

        $cancelledApp = BpjsApInvoice::factory()->cancelled()->create([
            'periode' => '2026-05',
            'sap_cancelled' => true,
        ]);

        $this->mock(SapService::class, function ($mock) {
            $mock->shouldReceive('getPurchaseInvoiceStatus')->andReturn(null);
        });

        $records = collect(
            $this->actingAs($user)
                ->getJson(route('bpjs-ap-invoices.data'))
                ->assertOk()
                ->json('data')
        );

        $this->assertStringContainsString('bpjs-repost-sap-btn', (string) $records->firstWhere('id', $cancelledInSap->id)['action']);
        $this->assertStringNotContainsString('bpjs-repost-sap-btn', (string) $records->firstWhere('id', $openInSap->id)['action']);
        $this->assertStringNotContainsString('bpjs-repost-sap-btn', (string) $records->firstWhere('id', $paidPartial->id)['action']);
        $this->assertStringNotContainsString('bpjs-repost-sap-btn', (string) $records->firstWhere('id', $cancelledApp->id)['action']);
    }

    public function test_repost_rejects_when_invoice_never_posted(): void
    {
        $invoice = BpjsApInvoice::factory()->create();

        $this->mock(SapService::class, function ($mock) {
            $mock->shouldNotReceive('createApInvoice');
            $mock->shouldNotReceive('getPurchaseInvoiceStatus');
        });

        $this->actingAs($this->authorizedUser())
            ->post(route('bpjs-ap-invoices.repost-sap', $invoice))
            ->assertRedirect()
            ->assertSessionHas('error', 'Invoice belum pernah diposting ke SAP.');
    }

    public function test_repost_rejects_when_cancelled_in_application(): void
    {
        $invoice = BpjsApInvoice::factory()->cancelled()->create([
            'sap_cancelled' => true,
        ]);

        $this->mock(SapService::class, function ($mock) {
            $mock->shouldNotReceive('createApInvoice');
            $mock->shouldNotReceive('getPurchaseInvoiceStatus');
        });

        $this->actingAs($this->authorizedUser())
            ->post(route('bpjs-ap-invoices.repost-sap', $invoice))
            ->assertRedirect()
            ->assertSessionHas('error', 'Invoice sudah dibatalkan di aplikasi, tidak bisa diposting ulang.');
    }

    public function test_repost_rejects_when_paid_amount_positive(): void
    {
        $invoice = $this->postedCancelledInSapInvoice(['paid_amount' => 5000]);

        $this->mock(SapService::class, function ($mock) {
            $mock->shouldNotReceive('createApInvoice');
            $mock->shouldNotReceive('getPurchaseInvoiceStatus');
        });

        $this->actingAs($this->authorizedUser())
            ->post(route('bpjs-ap-invoices.repost-sap', $invoice))
            ->assertRedirect()
            ->assertSessionHas('error', 'Invoice sudah memiliki pembayaran tercatat (paid_amount > 0).');
    }

    public function test_repost_rejects_when_sap_document_still_active(): void
    {
        $invoice = BpjsApInvoice::factory()->posted()->create([
            'sap_cancelled' => true,
            'sap_document_status' => 'bost_Open',
            'sap_doc_entry' => 90001,
        ]);

        $this->mock(SapService::class, function ($mock) {
            $mock->shouldReceive('getPurchaseInvoiceStatus')
                ->twice()
                ->andReturn([
                    'DocEntry' => 90001,
                    'DocNum' => 55001,
                    'DocumentStatus' => 'bost_Open',
                    'Cancelled' => 'tNO',
                ]);
            $mock->shouldNotReceive('createApInvoice');
        });

        $this->actingAs($this->authorizedUser())
            ->post(route('bpjs-ap-invoices.repost-sap', $invoice))
            ->assertRedirect()
            ->assertSessionHas('error', 'AP Invoice di SAP masih aktif (belum dibatalkan) — posting ulang tidak diperlukan.');

        $invoice->refresh();
        $this->assertFalse($invoice->sap_cancelled);
    }

    public function test_repost_success_creates_new_sap_document_and_logs(): void
    {
        $invoice = $this->postedCancelledInSapInvoice([
            'sap_doc_num' => '267007588',
            'sap_doc_entry' => 28625,
            'num_at_card' => '8/26',
            'label' => 'BPJS Kesehatan HO per Agustus 2026',
            'je_status' => BpjsApInvoice::JE_STATUS_FAILED,
        ]);

        $expectedPayload = (new SapBpjsApInvoiceBuilder($invoice))->build();

        $this->mock(SapService::class, function ($mock) use ($expectedPayload) {
            $mock->shouldReceive('getPurchaseInvoiceStatus')
                ->times(3)
                ->andReturn(
                    [
                        'DocEntry' => 28625,
                        'DocNum' => 267007588,
                        'DocumentStatus' => 'bost_Close',
                        'Cancelled' => 'tYES',
                    ],
                    [
                        'DocEntry' => 28625,
                        'DocNum' => 267007588,
                        'DocumentStatus' => 'bost_Close',
                        'Cancelled' => 'tYES',
                    ],
                    [
                        'DocEntry' => 28699,
                        'DocNum' => 267008001,
                        'DocumentStatus' => 'bost_Open',
                        'Cancelled' => 'tNO',
                    ],
                );

            $mock->shouldReceive('createApInvoice')
                ->once()
                ->withArgs(function (array $payload) use ($expectedPayload) {
                    $this->assertSame($expectedPayload['CardCode'], $payload['CardCode']);
                    $this->assertSame($expectedPayload['NumAtCard'], $payload['NumAtCard']);
                    $this->assertSame($expectedPayload['Comments'], $payload['Comments']);
                    $this->assertSame($expectedPayload['DocumentLines'][0]['AccountCode'], $payload['DocumentLines'][0]['AccountCode']);
                    $this->assertSame($expectedPayload['DocumentLines'][0]['UnitPrice'], $payload['DocumentLines'][0]['UnitPrice']);

                    return true;
                })
                ->andReturn([
                    'success' => true,
                    'doc_num' => '267008001',
                    'doc_entry' => 28699,
                    'data' => ['DocEntry' => 28699, 'DocNum' => 267008001],
                ]);
        });

        $response = $this->actingAs($this->authorizedUser())
            ->post(route('bpjs-ap-invoices.repost-sap', $invoice))
            ->assertRedirect()
            ->assertSessionHas('success');

        $invoice->refresh();
        $this->assertSame('267008001', $invoice->sap_doc_num);
        $this->assertSame(28699, (int) $invoice->sap_doc_entry);
        $this->assertSame(BpjsApInvoice::STATUS_POSTED, $invoice->status);
        $this->assertSame('267007588', $invoice->sap_previous_doc_num);
        $this->assertFalse($invoice->sap_cancelled);

        $this->assertDatabaseHas('sap_submission_logs', [
            'bpjs_ap_invoice_id' => $invoice->id,
            'document_type' => SapSubmissionLog::DOCUMENT_TYPE_BPJS_AP_INVOICE,
            'status' => 'success',
            'action' => 'submission',
            'sap_doc_num' => '267008001',
            'sap_doc_entry' => 28699,
        ]);

        $message = (string) $response->getSession()->get('success');
        $this->assertStringContainsString('267008001', $message);
        $this->assertStringContainsString('Jurnal akrual masih gagal', $message);
    }

    public function test_second_repost_after_success_is_rejected_without_calling_sap_create(): void
    {
        $invoice = $this->postedCancelledInSapInvoice([
            'sap_doc_num' => '267007588',
            'sap_doc_entry' => 28625,
        ]);

        $this->mock(SapService::class, function ($mock) {
            $mock->shouldReceive('getPurchaseInvoiceStatus')
                ->times(3)
                ->andReturn(
                    [
                        'DocEntry' => 28625,
                        'DocumentStatus' => 'bost_Close',
                        'Cancelled' => 'tYES',
                    ],
                    [
                        'DocEntry' => 28625,
                        'DocumentStatus' => 'bost_Close',
                        'Cancelled' => 'tYES',
                    ],
                    [
                        'DocEntry' => 28699,
                        'DocumentStatus' => 'bost_Open',
                        'Cancelled' => 'tNO',
                    ],
                );

            $mock->shouldReceive('createApInvoice')
                ->once()
                ->andReturn([
                    'success' => true,
                    'doc_num' => '267008001',
                    'doc_entry' => 28699,
                    'data' => [],
                ]);
        });

        $user = $this->authorizedUser();

        $this->actingAs($user)
            ->post(route('bpjs-ap-invoices.repost-sap', $invoice))
            ->assertSessionHas('success');

        $invoice->refresh();

        $this->mock(SapService::class, function ($mock) {
            $mock->shouldReceive('getPurchaseInvoiceStatus')
                ->twice()
                ->andReturn([
                    'DocEntry' => 28699,
                    'DocumentStatus' => 'bost_Open',
                    'Cancelled' => 'tNO',
                ]);
            $mock->shouldNotReceive('createApInvoice');
        });

        $this->actingAs($user)
            ->post(route('bpjs-ap-invoices.repost-sap', $invoice))
            ->assertRedirect()
            ->assertSessionHas('error', 'AP Invoice di SAP masih aktif (belum dibatalkan) — posting ulang tidak diperlukan.');
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function postedCancelledInSapInvoice(array $overrides = []): BpjsApInvoice
    {
        return BpjsApInvoice::factory()->posted()->create(array_merge([
            'sap_cancelled' => true,
            'sap_document_status' => 'bost_Close',
            'paid_amount' => 0,
            'num_at_card' => '10/26',
            'label' => 'BPJS Kesehatan HO per Oktober 2026',
        ], $overrides));
    }

    private function authorizedUser(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['akses_ap_invoice_bpjs', 'submit_sap_ap_invoice_bpjs']);

        return $user;
    }
}
