<?php

namespace Tests\Feature;

use App\Models\BpjsApInvoice;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Project;
use App\Models\SapBusinessPartner;
use App\Models\User;
use App\Services\BpjsTkAccrualJournalService;
use App\Services\SapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class BpjsTkAccrualJournalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'akses_ap_invoice_bpjs', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'submit_sap_ap_invoice_bpjs', 'guard_name' => 'web']);

        SapBusinessPartner::query()->create([
            'code' => 'VBPTKIDR01',
            'name' => 'BPJS KETENAGAKERJAAN',
            'type' => SapBusinessPartner::TYPE_SUPPLIER,
            'active' => true,
        ]);

        SapBusinessPartner::query()->create([
            'code' => 'VBPKEIDR01',
            'name' => 'BPJS KESEHATAN',
            'type' => SapBusinessPartner::TYPE_SUPPLIER,
            'active' => true,
        ]);

        Project::query()->create([
            'code' => '022C',
            'name' => 'NS 022C',
            'is_active' => true,
            'is_selectable' => true,
        ]);

        Project::query()->create([
            'code' => '000H',
            'name' => 'Head Office',
            'is_active' => true,
            'is_selectable' => true,
        ]);
    }

    public function test_tk_accrual_journal_uses_correct_posting_date_accounts_and_amount(): void
    {
        $user = $this->authorizedUser();
        $invoice = $this->makeTkInvoice([
            'periode' => '2026-09',
            'amount' => 8000000,
            'je_posting_date' => BpjsApInvoice::defaultJePostingDate('2026-09'),
        ]);

        $this->mockSapApAndJeSuccess();

        $this->actingAs($user)
            ->post(route('bpjs-ap-invoices.submit', $invoice))
            ->assertRedirect(route('bpjs-ap-invoices.index'))
            ->assertSessionHas('success');

        $invoice->refresh();
        $this->assertSame(BpjsApInvoice::STATUS_POSTED, $invoice->status);
        $this->assertSame(BpjsApInvoice::JE_STATUS_SUCCESS, $invoice->je_status);
        $this->assertNotNull($invoice->journal_entry_id);

        $journalEntry = JournalEntry::with('lines')->findOrFail($invoice->journal_entry_id);
        $this->assertSame('2026-08-31', $journalEntry->date->format('Y-m-d'));

        $debitLine = $journalEntry->lines->firstWhere('debit_credit', 'debit');
        $creditLine = $journalEntry->lines->firstWhere('debit_credit', 'credit');

        $this->assertSame('61201003', $debitLine->account_code);
        $this->assertSame('21601001', $creditLine->account_code);
        $this->assertSame(8000000.0, (float) $debitLine->amount);
        $this->assertSame(8000000.0, (float) $creditLine->amount);
        $this->assertSame('022C', $debitLine->project);
        $this->assertSame('022C', $creditLine->project);
        $this->assertSame('20', $debitLine->cost_center);
        $this->assertSame('20', $creditLine->cost_center);
    }

    public function test_auto_je_disabled_skips_journal_without_creating_entry(): void
    {
        $user = $this->authorizedUser();
        $invoice = $this->makeTkInvoice(['auto_je' => false]);

        $this->mock(SapService::class, function ($mock) {
            $mock->shouldReceive('createApInvoice')
                ->once()
                ->andReturn([
                    'success' => true,
                    'doc_num' => '55002',
                    'doc_entry' => 28626,
                ]);
            $mock->shouldNotReceive('createJournalEntry');
        });

        $this->actingAs($user)
            ->post(route('bpjs-ap-invoices.submit', $invoice))
            ->assertRedirect(route('bpjs-ap-invoices.index'));

        $invoice->refresh();
        $this->assertSame(BpjsApInvoice::STATUS_POSTED, $invoice->status);
        $this->assertSame(BpjsApInvoice::JE_STATUS_SKIPPED, $invoice->je_status);
        $this->assertNull($invoice->journal_entry_id);
        $this->assertSame(0, JournalEntry::count());
    }

    public function test_kesehatan_submit_never_creates_accrual_journal(): void
    {
        $user = $this->authorizedUser();
        $invoice = BpjsApInvoice::factory()->create([
            'jenis' => BpjsApInvoice::JENIS_KESEHATAN,
            'unit' => '000H',
            'periode' => '2026-10',
            'num_at_card' => '10/26',
            'label' => 'BPJS Kesehatan HO per Oktober 2026',
            'auto_je' => false,
        ]);

        $this->mock(SapService::class, function ($mock) {
            $mock->shouldReceive('createApInvoice')
                ->once()
                ->andReturn([
                    'success' => true,
                    'doc_num' => '55003',
                    'doc_entry' => 28627,
                ]);
            $mock->shouldNotReceive('createJournalEntry');
        });

        $this->actingAs($user)
            ->post(route('bpjs-ap-invoices.submit', $invoice))
            ->assertRedirect(route('bpjs-ap-invoices.index'));

        $invoice->refresh();
        $this->assertSame(BpjsApInvoice::STATUS_POSTED, $invoice->status);
        $this->assertNull($invoice->je_status);
        $this->assertNull($invoice->journal_entry_id);
        $this->assertSame(0, JournalEntry::count());
    }

    public function test_je_failure_keeps_ap_posted_and_retry_succeeds_without_duplicate_je(): void
    {
        $user = $this->authorizedUser();
        $invoice = $this->makeTkInvoice();

        $this->mock(SapService::class, function ($mock) {
            $mock->shouldReceive('createApInvoice')
                ->once()
                ->andReturn([
                    'success' => true,
                    'doc_num' => '55004',
                    'doc_entry' => 28628,
                ]);
            $mock->shouldReceive('createJournalEntry')
                ->once()
                ->andThrow(new \Exception('SAP JE connection failed'));
        });

        $this->actingAs($user)
            ->post(route('bpjs-ap-invoices.submit', $invoice))
            ->assertRedirect(route('bpjs-ap-invoices.index'));

        $invoice->refresh();
        $this->assertSame(BpjsApInvoice::STATUS_POSTED, $invoice->status);
        $this->assertSame(BpjsApInvoice::JE_STATUS_FAILED, $invoice->je_status);
        $this->assertNotNull($invoice->journal_entry_id);
        $this->assertStringContainsString('SAP JE connection failed', (string) $invoice->je_error);
        $this->assertSame(1, JournalEntry::count());

        $existingJeId = $invoice->journal_entry_id;

        $this->mock(SapService::class, function ($mock) {
            $mock->shouldReceive('createJournalEntry')
                ->once()
                ->andReturn([
                    'success' => true,
                    'journal_number' => 'SAP-JE-200',
                    'data' => [
                        'DocEntry' => 9002,
                        'JdtNum' => 9002,
                        'Number' => 'SAP-JE-200',
                    ],
                ]);
        });

        $this->actingAs($user)
            ->post(route('bpjs-ap-invoices.retry-je', $invoice))
            ->assertRedirect(route('bpjs-ap-invoices.index'))
            ->assertSessionHas('success');

        $invoice->refresh();
        $this->assertSame(BpjsApInvoice::JE_STATUS_SUCCESS, $invoice->je_status);
        $this->assertSame($existingJeId, $invoice->journal_entry_id);
        $this->assertSame(1, JournalEntry::count());
        $this->assertNull($invoice->je_error);

        $serviceResult = app(BpjsTkAccrualJournalService::class)->createAndSubmit($invoice, $user);
        $this->assertTrue($serviceResult['success']);
        $this->assertSame(1, JournalEntry::count());
    }

    public function test_service_idempotent_when_je_already_success(): void
    {
        $user = $this->authorizedUser();
        $journalEntry = JournalEntry::factory()->posted()->create(['created_by' => $user->id]);

        JournalEntryLine::factory()->create([
            'journal_entry_id' => $journalEntry->id,
            'line_no' => 1,
            'account_code' => '61201003',
            'debit_credit' => 'debit',
            'amount' => 5000000,
        ]);

        JournalEntryLine::factory()->create([
            'journal_entry_id' => $journalEntry->id,
            'line_no' => 2,
            'account_code' => '21601001',
            'debit_credit' => 'credit',
            'amount' => 5000000,
        ]);

        $invoice = $this->makeTkInvoice([
            'status' => BpjsApInvoice::STATUS_POSTED,
            'journal_entry_id' => $journalEntry->id,
            'je_status' => BpjsApInvoice::JE_STATUS_SUCCESS,
        ]);

        $result = app(BpjsTkAccrualJournalService::class)->createAndSubmit($invoice, $user);

        $this->assertTrue($result['success']);
        $this->assertSame(1, JournalEntry::count());
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeTkInvoice(array $overrides = []): BpjsApInvoice
    {
        return BpjsApInvoice::factory()->create(array_merge([
            'jenis' => BpjsApInvoice::JENIS_KETENAGAKERJAAN,
            'unit' => '022C',
            'periode' => '2026-09',
            'amount' => 8000000,
            'doc_date' => '2026-09-01',
            'due_date' => '2026-10-01',
            'num_at_card' => '9/26',
            'label' => 'BPJS Ketenagakerjaan NS 022C per September 2026',
            'auto_je' => true,
            'je_posting_date' => BpjsApInvoice::defaultJePostingDate('2026-09'),
        ], $overrides));
    }

    private function mockSapApAndJeSuccess(): void
    {
        $this->mock(SapService::class, function ($mock) {
            $mock->shouldReceive('createApInvoice')
                ->once()
                ->andReturn([
                    'success' => true,
                    'doc_num' => '55001',
                    'doc_entry' => 28625,
                ]);
            $mock->shouldReceive('createJournalEntry')
                ->once()
                ->andReturn([
                    'success' => true,
                    'journal_number' => 'SAP-JE-100',
                    'data' => [
                        'DocEntry' => 9001,
                        'JdtNum' => 9001,
                        'Number' => 'SAP-JE-100',
                    ],
                ]);
        });
    }

    private function authorizedUser(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['akses_ap_invoice_bpjs', 'submit_sap_ap_invoice_bpjs']);

        return $user;
    }
}
