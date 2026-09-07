<?php

namespace Tests\Feature;

use App\Models\BpjsApInvoice;
use App\Models\Project;
use App\Models\SapBusinessPartner;
use App\Models\SapSubmissionLog;
use App\Models\User;
use App\Services\SapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class BpjsApInvoiceControllerTest extends TestCase
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

    public function test_store_rejects_duplicate_jenis_unit_periode(): void
    {
        BpjsApInvoice::factory()->posted()->create([
            'jenis' => BpjsApInvoice::JENIS_KESEHATAN,
            'unit' => '000H',
            'periode' => '2026-10',
        ]);

        $user = $this->authorizedUser();

        $this->actingAs($user)
            ->post(route('bpjs-ap-invoices.store'), $this->validPayload())
            ->assertRedirect(route('bpjs-ap-invoices.index'))
            ->assertSessionHas('error');
    }

    public function test_submit_success_updates_posted_status_and_sap_doc_num(): void
    {
        $invoice = BpjsApInvoice::factory()->create([
            'jenis' => BpjsApInvoice::JENIS_KESEHATAN,
            'unit' => '000H',
            'periode' => '2026-10',
            'num_at_card' => '10/26',
            'label' => 'BPJS Kesehatan HO per Oktober 2026',
        ]);

        $this->mock(SapService::class, function ($mock) {
            $mock->shouldReceive('createApInvoice')
                ->once()
                ->andReturn([
                    'success' => true,
                    'doc_num' => '55001',
                    'doc_entry' => 28625,
                    'data' => ['DocEntry' => 28625, 'DocNum' => 55001],
                ]);
        });

        $this->actingAs($this->authorizedUser())
            ->post(route('bpjs-ap-invoices.submit', $invoice))
            ->assertRedirect(route('bpjs-ap-invoices.index'))
            ->assertSessionHas('success');

        $invoice->refresh();
        $this->assertSame(BpjsApInvoice::STATUS_POSTED, $invoice->status);
        $this->assertSame('55001', $invoice->sap_doc_num);
        $this->assertSame(28625, (int) $invoice->sap_doc_entry);

        $this->assertDatabaseHas('sap_submission_logs', [
            'bpjs_ap_invoice_id' => $invoice->id,
            'document_type' => SapSubmissionLog::DOCUMENT_TYPE_BPJS_AP_INVOICE,
            'status' => 'success',
        ]);
    }

    public function test_submit_failure_marks_invoice_failed_and_logs_error(): void
    {
        $invoice = BpjsApInvoice::factory()->create([
            'num_at_card' => '10/26',
            'label' => 'BPJS Kesehatan HO per Oktober 2026',
        ]);

        $this->mock(SapService::class, function ($mock) {
            $mock->shouldReceive('createApInvoice')
                ->once()
                ->andReturn([
                    'success' => false,
                    'message' => 'SAP rejected document',
                ]);
        });

        $this->actingAs($this->authorizedUser())
            ->post(route('bpjs-ap-invoices.submit', $invoice))
            ->assertRedirect(route('bpjs-ap-invoices.preview', $invoice))
            ->assertSessionHas('error');

        $invoice->refresh();
        $this->assertSame(BpjsApInvoice::STATUS_FAILED, $invoice->status);
        $this->assertStringContainsString('SAP rejected', (string) $invoice->sap_error_message);

        $this->assertDatabaseHas('sap_submission_logs', [
            'bpjs_ap_invoice_id' => $invoice->id,
            'document_type' => SapSubmissionLog::DOCUMENT_TYPE_BPJS_AP_INVOICE,
            'status' => 'failed',
        ]);
    }

    public function test_last_amount_endpoint_returns_previous_month_amount(): void
    {
        BpjsApInvoice::factory()->create([
            'jenis' => BpjsApInvoice::JENIS_KESEHATAN,
            'unit' => '000H',
            'periode' => '2025-12',
            'amount' => 2500000,
        ]);

        $this->actingAs($this->authorizedUser())
            ->getJson(route('bpjs-ap-invoices.last-amount', [
                'jenis' => BpjsApInvoice::JENIS_KESEHATAN,
                'unit' => '000H',
                'periode' => '2026-01',
            ]))
            ->assertOk()
            ->assertJson([
                'found' => true,
                'amount' => 2500000.0,
                'periode_sumber' => '2025-12',
            ]);
    }

    public function test_data_endpoint_requires_permission(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson(route('bpjs-ap-invoices.data'))
            ->assertForbidden();
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(): array
    {
        return [
            'jenis' => BpjsApInvoice::JENIS_KESEHATAN,
            'unit' => '000H',
            'periode' => '2026-10',
            'amount' => 5000000,
            'doc_date' => '2026-09-07',
            'due_date' => '2026-10-07',
        ];
    }

    private function authorizedUser(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['akses_ap_invoice_bpjs', 'submit_sap_ap_invoice_bpjs']);

        return $user;
    }
}
