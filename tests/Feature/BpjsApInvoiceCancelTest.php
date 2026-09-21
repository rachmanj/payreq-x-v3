<?php

namespace Tests\Feature;

use App\Models\BpjsApInvoice;
use App\Models\JournalEntry;
use App\Models\Project;
use App\Models\SapBusinessPartner;
use App\Models\SapSubmissionLog;
use App\Models\User;
use App\Services\JournalEntrySubmissionService;
use App\Services\SapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class BpjsApInvoiceCancelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'akses_ap_invoice_bpjs', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'submit_sap_ap_invoice_bpjs', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'cancel_sap_ap_invoice_bpjs', 'guard_name' => 'web']);

        SapBusinessPartner::query()->create([
            'code' => 'VBPKEIDR01',
            'name' => 'BPJS KESEHATAN',
            'type' => SapBusinessPartner::TYPE_SUPPLIER,
            'active' => true,
        ]);

        SapBusinessPartner::query()->create([
            'code' => 'VBPTKIDR01',
            'name' => 'BPJS KETENAGAKERJAAN',
            'type' => SapBusinessPartner::TYPE_SUPPLIER,
            'active' => true,
        ]);

        Project::query()->create([
            'code' => '000H',
            'name' => 'Head Office',
            'is_active' => true,
            'is_selectable' => true,
        ]);

        Project::query()->create([
            'code' => '022C',
            'name' => 'NS 022C',
            'is_active' => true,
            'is_selectable' => true,
        ]);
    }

    public function test_cancel_kesehatan_success_without_je_reversal(): void
    {
        $invoice = BpjsApInvoice::factory()->posted()->create([
            'jenis' => BpjsApInvoice::JENIS_KESEHATAN,
            'unit' => '000H',
            'periode' => '2026-10',
            'sap_doc_entry' => 28625,
            'sap_doc_num' => '55001',
        ]);

        $this->mockSapCancelSuccess();

        $this->mock(JournalEntrySubmissionService::class, function ($mock) {
            $mock->shouldNotReceive('reverse');
        });

        $user = $this->cancelAuthorizedUser();

        $this->actingAs($user)
            ->post(route('bpjs-ap-invoices.cancel', $invoice), [
                'cancel_reason' => 'Salah periode posting',
            ])
            ->assertRedirect(route('bpjs-ap-invoices.index'))
            ->assertSessionHas('success');

        $invoice->refresh();
        $this->assertSame(BpjsApInvoice::STATUS_CANCELLED, $invoice->status);
        $this->assertNotNull($invoice->cancelled_at);
        $this->assertSame($user->id, (int) $invoice->cancelled_by);
        $this->assertSame('Salah periode posting', $invoice->cancel_reason);

        $this->assertDatabaseHas('sap_submission_logs', [
            'bpjs_ap_invoice_id' => $invoice->id,
            'document_type' => SapSubmissionLog::DOCUMENT_TYPE_BPJS_AP_INVOICE_CANCELLATION,
            'status' => 'success',
            'action' => 'cancellation',
        ]);
    }

    public function test_cancel_tk_success_reverses_accrual_journal(): void
    {
        $journalEntry = JournalEntry::factory()->posted()->create();
        $invoice = BpjsApInvoice::factory()->posted()->create([
            'jenis' => BpjsApInvoice::JENIS_KETENAGAKERJAAN,
            'unit' => '022C',
            'periode' => '2026-09',
            'sap_doc_entry' => 28626,
            'sap_doc_num' => '55002',
            'journal_entry_id' => $journalEntry->id,
            'je_status' => BpjsApInvoice::JE_STATUS_SUCCESS,
        ]);

        $this->mockSapCancelSuccess(28626);

        $this->mock(JournalEntrySubmissionService::class, function ($mock) {
            $mock->shouldReceive('reverse')
                ->once()
                ->andReturn([
                    'success' => true,
                    'message' => 'Reversed',
                ]);
        });

        $this->actingAs($this->cancelAuthorizedUser())
            ->post(route('bpjs-ap-invoices.cancel', $invoice), [
                'cancel_reason' => 'Duplikat submit ke SAP',
            ])
            ->assertRedirect(route('bpjs-ap-invoices.index'))
            ->assertSessionHas('success');

        $invoice->refresh();
        $this->assertSame(BpjsApInvoice::STATUS_CANCELLED, $invoice->status);
        $this->assertSame(BpjsApInvoice::JE_STATUS_REVERSED, $invoice->je_status);
        $this->assertNull($invoice->je_error);
    }

    public function test_cancel_rejects_non_posted_status(): void
    {
        $pending = BpjsApInvoice::factory()->create([
            'status' => BpjsApInvoice::STATUS_PENDING,
            'sap_doc_entry' => 100,
        ]);
        $failed = BpjsApInvoice::factory()->failed()->create([
            'sap_doc_entry' => 101,
        ]);

        $this->mock(SapService::class, function ($mock) {
            $mock->shouldNotReceive('getPurchaseInvoiceByDocEntry');
            $mock->shouldNotReceive('cancelPurchaseInvoice');
        });

        $user = $this->cancelAuthorizedUser();

        $this->actingAs($user)
            ->post(route('bpjs-ap-invoices.cancel', $pending), ['cancel_reason' => 'Batalkan pending'])
            ->assertRedirect(route('bpjs-ap-invoices.index'))
            ->assertSessionHas('error');

        $this->actingAs($user)
            ->post(route('bpjs-ap-invoices.cancel', $failed), ['cancel_reason' => 'Batalkan failed'])
            ->assertRedirect(route('bpjs-ap-invoices.index'))
            ->assertSessionHas('error');

        $this->assertSame(BpjsApInvoice::STATUS_PENDING, $pending->fresh()->status);
        $this->assertSame(BpjsApInvoice::STATUS_FAILED, $failed->fresh()->status);
    }

    public function test_cancel_rejects_paid_invoice(): void
    {
        $invoice = BpjsApInvoice::factory()->paid()->create([
            'jenis' => BpjsApInvoice::JENIS_KESEHATAN,
            'unit' => '000H',
            'periode' => '2026-10',
        ]);

        $this->mock(SapService::class, function ($mock) {
            $mock->shouldNotReceive('cancelPurchaseInvoice');
        });

        $this->actingAs($this->cancelAuthorizedUser())
            ->post(route('bpjs-ap-invoices.cancel', $invoice), [
                'cancel_reason' => 'Coba batalkan paid',
            ])
            ->assertRedirect(route('bpjs-ap-invoices.index'))
            ->assertSessionHas('error');

        $this->assertSame(BpjsApInvoice::STATUS_PAID, $invoice->fresh()->status);
    }

    public function test_sap_cancel_error_keeps_invoice_posted(): void
    {
        $invoice = BpjsApInvoice::factory()->posted()->create([
            'sap_doc_entry' => 28627,
            'sap_doc_num' => '55003',
        ]);

        $this->mock(SapService::class, function ($mock) {
            $mock->shouldReceive('getPurchaseInvoiceByDocEntry')
                ->once()
                ->andReturn($this->openSapInvoice(28627));
            $mock->shouldReceive('cancelPurchaseInvoice')
                ->once()
                ->andReturn([
                    'success' => false,
                    'message' => 'SAP rejected cancellation',
                    'data' => null,
                ]);
        });

        $this->actingAs($this->cancelAuthorizedUser())
            ->post(route('bpjs-ap-invoices.cancel', $invoice), [
                'cancel_reason' => 'Gagal di SAP',
            ])
            ->assertRedirect(route('bpjs-ap-invoices.index'))
            ->assertSessionHas('error', 'SAP rejected cancellation');

        $invoice->refresh();
        $this->assertSame(BpjsApInvoice::STATUS_POSTED, $invoice->status);
        $this->assertNull($invoice->cancelled_at);
    }

    public function test_je_reversal_failure_then_retry_succeeds_without_double_reversal(): void
    {
        $journalEntry = JournalEntry::factory()->posted()->create();
        $invoice = BpjsApInvoice::factory()->posted()->create([
            'jenis' => BpjsApInvoice::JENIS_KETENAGAKERJAAN,
            'unit' => '022C',
            'periode' => '2026-09',
            'sap_doc_entry' => 28628,
            'journal_entry_id' => $journalEntry->id,
            'je_status' => BpjsApInvoice::JE_STATUS_SUCCESS,
        ]);

        $this->mockSapCancelSuccess(28628);

        $this->mock(JournalEntrySubmissionService::class, function ($mock) {
            $mock->shouldReceive('reverse')
                ->once()
                ->ordered()
                ->andReturn([
                    'success' => false,
                    'message' => 'SAP JE reversal timeout',
                ]);
            $mock->shouldReceive('reverse')
                ->once()
                ->ordered()
                ->andReturn([
                    'success' => true,
                    'message' => 'Reversed on retry',
                ]);
        });

        $user = $this->cancelAuthorizedUser();

        $this->actingAs($user)
            ->post(route('bpjs-ap-invoices.cancel', $invoice), [
                'cancel_reason' => 'Retry reversal scenario',
            ])
            ->assertRedirect(route('bpjs-ap-invoices.index'))
            ->assertSessionHas('success');

        $invoice->refresh();
        $this->assertSame(BpjsApInvoice::STATUS_CANCELLED, $invoice->status);
        $this->assertSame(BpjsApInvoice::JE_STATUS_FAILED, $invoice->je_status);
        $this->assertStringContainsString('SAP JE reversal timeout', (string) $invoice->je_error);

        $this->actingAs($user)
            ->post(route('bpjs-ap-invoices.cancel-je', $invoice))
            ->assertRedirect(route('bpjs-ap-invoices.index'))
            ->assertSessionHas('success');

        $invoice->refresh();
        $this->assertSame(BpjsApInvoice::JE_STATUS_REVERSED, $invoice->je_status);
        $this->assertNull($invoice->je_error);
    }

    public function test_second_cancel_attempt_is_rejected(): void
    {
        $invoice = BpjsApInvoice::factory()->posted()->create([
            'sap_doc_entry' => 28629,
        ]);

        $this->mockSapCancelSuccess(28629);

        $this->mock(JournalEntrySubmissionService::class, function ($mock) {
            $mock->shouldNotReceive('reverse');
        });

        $user = $this->cancelAuthorizedUser();

        $this->actingAs($user)
            ->post(route('bpjs-ap-invoices.cancel', $invoice), [
                'cancel_reason' => 'Pembatalan pertama',
            ])
            ->assertRedirect(route('bpjs-ap-invoices.index'))
            ->assertSessionHas('success');

        $this->mock(SapService::class, function ($mock) {
            $mock->shouldNotReceive('cancelPurchaseInvoice');
        });

        $this->actingAs($user)
            ->post(route('bpjs-ap-invoices.cancel', $invoice->fresh()), [
                'cancel_reason' => 'Pembatalan kedua',
            ])
            ->assertRedirect(route('bpjs-ap-invoices.index'))
            ->assertSessionHas('error');

        $this->assertSame(BpjsApInvoice::STATUS_CANCELLED, $invoice->fresh()->status);
    }

    public function test_cancel_requires_permission(): void
    {
        $invoice = BpjsApInvoice::factory()->posted()->create([
            'sap_doc_entry' => 28630,
        ]);

        $user = User::factory()->create();
        $user->givePermissionTo(['akses_ap_invoice_bpjs', 'submit_sap_ap_invoice_bpjs']);

        $this->actingAs($user)
            ->postJson(route('bpjs-ap-invoices.cancel', $invoice), [
                'cancel_reason' => 'Tanpa permission',
            ])
            ->assertForbidden();
    }

    public function test_cancel_reason_validation_requires_minimum_length(): void
    {
        $invoice = BpjsApInvoice::factory()->posted()->create([
            'sap_doc_entry' => 28631,
        ]);

        $this->actingAs($this->cancelAuthorizedUser())
            ->post(route('bpjs-ap-invoices.cancel', $invoice), [
                'cancel_reason' => 'abc',
            ])
            ->assertSessionHasErrors(['cancel_reason']);

        $this->assertSame(BpjsApInvoice::STATUS_POSTED, $invoice->fresh()->status);
    }

    public function test_new_invoice_can_be_created_after_cancellation_for_same_period(): void
    {
        BpjsApInvoice::factory()->cancelled()->create([
            'jenis' => BpjsApInvoice::JENIS_KESEHATAN,
            'unit' => '000H',
            'periode' => '2026-10',
        ]);

        $user = User::factory()->create();
        $user->givePermissionTo(['akses_ap_invoice_bpjs', 'submit_sap_ap_invoice_bpjs']);

        $this->actingAs($user)
            ->post(route('bpjs-ap-invoices.store'), [
                'jenis' => BpjsApInvoice::JENIS_KESEHATAN,
                'unit' => '000H',
                'periode' => '2026-10',
                'amount' => 5000000,
                'doc_date' => '2026-09-07',
                'due_date' => '2026-10-07',
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('bpjs_ap_invoices', 2);
        $this->assertDatabaseHas('bpjs_ap_invoices', [
            'jenis' => BpjsApInvoice::JENIS_KESEHATAN,
            'unit' => '000H',
            'periode' => '2026-10',
            'status' => BpjsApInvoice::STATUS_PENDING,
        ]);
    }

    private function mockSapCancelSuccess(int $docEntry = 28625): void
    {
        $this->mock(SapService::class, function ($mock) use ($docEntry) {
            $mock->shouldReceive('getPurchaseInvoiceByDocEntry')
                ->once()
                ->andReturn($this->openSapInvoice($docEntry));
            $mock->shouldReceive('cancelPurchaseInvoice')
                ->once()
                ->andReturn([
                    'success' => true,
                    'message' => 'Purchase invoice cancelled successfully.',
                    'data' => array_merge($this->openSapInvoice($docEntry), ['Cancelled' => 'tYES']),
                ]);
            $mock->shouldReceive('getPurchaseInvoiceStatus')
                ->andReturn(array_merge($this->openSapInvoice($docEntry), [
                    'Cancelled' => 'tYES',
                    'DocumentStatus' => 'bost_Open',
                ]));
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function openSapInvoice(int $docEntry): array
    {
        return [
            'DocEntry' => $docEntry,
            'DocNum' => (string) (55000 + ($docEntry - 28625)),
            'DocumentStatus' => 'bost_Open',
            'Cancelled' => 'tNO',
            'PaidToDate' => 0,
            'DocTotal' => 5000000,
        ];
    }

    private function cancelAuthorizedUser(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo([
            'akses_ap_invoice_bpjs',
            'submit_sap_ap_invoice_bpjs',
            'cancel_sap_ap_invoice_bpjs',
        ]);

        return $user;
    }
}
