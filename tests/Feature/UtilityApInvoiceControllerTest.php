<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\SapBusinessPartner;
use App\Models\SapSubmissionLog;
use App\Models\User;
use App\Models\UtilityApInvoice;
use App\Models\UtilityBill;
use App\Models\UtilityCustomer;
use App\Models\UtilityVendor;
use App\Services\SapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class UtilityApInvoiceControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::findOrCreate('akses_utilities', 'web');
        Permission::findOrCreate('submit_sap_ap_invoice_utilities', 'web');
        Permission::findOrCreate('submit_sap_utility_payment', 'web');
    }

    public function test_payment_accounts_requires_submit_sap_utility_payment_permission(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('akses_utilities');

        $this->actingAs($user)
            ->getJson(route('utilities.ap-invoices.accounts'))
            ->assertForbidden();

        $authorized = User::factory()->create();
        $authorized->givePermissionTo(['akses_utilities', 'submit_sap_utility_payment']);

        Account::query()->create([
            'account_number' => '11010001',
            'account_name' => 'BCA Operating',
            'type' => 'bank',
            'sap_account' => '11010001',
            'is_active' => true,
            'is_hidden' => false,
        ]);

        $this->actingAs($authorized)
            ->getJson(route('utilities.ap-invoices.accounts'))
            ->assertOk()
            ->assertJsonStructure(['accounts' => [['id', 'label', 'account_number', 'account_name', 'sap_account', 'type']]]);
    }

    public function test_preview_renders_for_valid_selection(): void
    {
        $user = $this->authorizedUser();
        $context = $this->createEligibleContext();

        $this->actingAs($user)
            ->post(route('utilities.bills.ap-invoice.preview.store'), [
                'bill_ids' => [$context['bill']->id],
            ])
            ->assertRedirect(route('utilities.bills.ap-invoice.preview'));

        $this->actingAs($user)
            ->get(route('utilities.bills.ap-invoice.preview'))
            ->assertOk()
            ->assertSee('VPLNPIDR01')
            ->assertSee('PLN 8/26')
            ->assertSee('61208001');
    }

    public function test_submit_creates_header_and_links_bills_on_success(): void
    {
        $user = $this->authorizedUser();
        $context = $this->createEligibleContext();

        $this->mock(SapService::class, function ($mock) {
            $mock->shouldReceive('createApInvoice')
                ->once()
                ->andReturn([
                    'success' => true,
                    'doc_entry' => 2026,
                    'doc_num' => '2026',
                    'data' => ['DocEntry' => 2026, 'DocNum' => 2026],
                ]);
        });

        $this->actingAs($user)
            ->from(route('utilities.bills.ap-invoice.preview'))
            ->post(route('utilities.bills.ap-invoice.submit'), [
                'bill_ids' => [$context['bill']->id],
                'num_at_card' => 'PLN 8/26',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $invoice = UtilityApInvoice::query()->first();
        $this->assertNotNull($invoice);
        $this->assertSame(UtilityApInvoice::STATUS_POSTED, $invoice->status);
        $this->assertSame('2026', $invoice->sap_doc_num);
        $this->assertSame(2026, (int) $invoice->sap_doc_entry);
        $this->assertSame($invoice->id, $context['bill']->fresh()->utility_ap_invoice_id);

        $this->assertDatabaseHas('sap_submission_logs', [
            'utility_ap_invoice_id' => $invoice->id,
            'document_type' => 'ap_invoice_utility',
            'status' => 'success',
            'action' => 'submission',
        ]);
    }

    public function test_submit_rolls_back_and_logs_failure_when_sap_throws(): void
    {
        $user = $this->authorizedUser();
        $context = $this->createEligibleContext();

        $this->actingAs($user)
            ->post(route('utilities.bills.ap-invoice.preview.store'), [
                'bill_ids' => [$context['bill']->id],
            ]);

        $this->mock(SapService::class, function ($mock) {
            $mock->shouldReceive('createApInvoice')
                ->once()
                ->andThrow(new \Exception('SAP connection failed'));
        });

        $this->actingAs($user)
            ->post(route('utilities.bills.ap-invoice.submit'), [
                'bill_ids' => [$context['bill']->id],
                'num_at_card' => 'PLN 8/26',
            ])
            ->assertRedirect(route('utilities.bills.ap-invoice.preview'))
            ->assertSessionHas('error');

        $this->assertSame(0, UtilityApInvoice::query()->count());
        $this->assertNull($context['bill']->fresh()->utility_ap_invoice_id);
        $this->assertDatabaseHas('sap_submission_logs', [
            'document_type' => 'ap_invoice_utility',
            'status' => 'failed',
            'action' => 'submission',
        ]);
        $this->assertStringContainsString(
            'SAP connection failed',
            (string) SapSubmissionLog::query()->where('status', 'failed')->value('error_message')
        );
    }

    public function test_mixed_jenis_selection_is_blocked(): void
    {
        $user = $this->authorizedUser();
        $pln = $this->createEligibleContext();
        $pdamBill = $this->createBill($pln['account'], [
            'jenis_utilitas' => 'pdam',
            'id_pelanggan' => 'PDAM-1',
        ]);

        UtilityVendor::query()->where('jenis_utilitas', 'pdam')->update([
            'sap_business_partner_id' => $pln['partner']->id,
        ]);

        $this->actingAs($user)
            ->post(route('utilities.bills.ap-invoice.preview.store'), [
                'bill_ids' => [$pln['bill']->id, $pdamBill->id],
            ])
            ->assertRedirect(route('utilities.bills.index'))
            ->assertSessionHas('error');
    }

    public function test_user_without_submit_permission_is_forbidden(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('akses_utilities');
        $context = $this->createEligibleContext();

        $this->actingAs($user)
            ->from(route('utilities.bills.index'))
            ->post(route('utilities.bills.ap-invoice.preview.store'), [
                'bill_ids' => [$context['bill']->id],
            ])
            ->assertRedirect(route('utilities.bills.index'))
            ->assertSessionHas('alert_type', 'error');
    }

    public function test_preview_sap_payment_includes_comments_matching_journal_remarks(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['akses_utilities', 'submit_sap_utility_payment']);

        $context = $this->createPostedUtilityApInvoiceContext();
        $expectedRemarks = 'Payment for Invoice '.$context['invoice']->num_at_card;

        $this->mock(SapService::class, function ($mock) use ($context) {
            $mock->shouldReceive('getPurchaseInvoiceByDocEntry')
                ->once()
                ->with($context['invoice']->sap_doc_entry)
                ->andReturn($this->openUtilityApInvoicePayload($context));
        });

        $this->actingAs($user)
            ->postJson(route('utilities.ap-invoices.sap-payment.preview', ['utilityApInvoice' => $context['invoice']->id]), [
                'payment_means' => 'transfer',
                'account_id' => $context['account']->id,
                'payment_date' => '2026-09-18',
                'payment_amount' => 14007142,
                'prepared_by' => 'John Preparer',
                'approved_by' => 'Jane Approver',
            ])
            ->assertOk()
            ->assertJsonPath('preview.journal_remarks', $expectedRemarks)
            ->assertJsonPath('preview.comments', $expectedRemarks);
    }

    public function test_submit_sap_payment_sends_comments_matching_journal_remarks(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['akses_utilities', 'submit_sap_utility_payment']);

        $context = $this->createPostedUtilityApInvoiceContext();
        $expectedRemarks = 'Payment for Invoice '.$context['invoice']->num_at_card;
        $submittedPayload = null;

        $this->mock(SapService::class, function ($mock) use ($context, &$submittedPayload) {
            $mock->shouldReceive('getPurchaseInvoiceByDocEntry')
                ->once()
                ->with($context['invoice']->sap_doc_entry)
                ->andReturn($this->openUtilityApInvoicePayload($context));

            $mock->shouldReceive('createOutgoingPayment')
                ->once()
                ->with(\Mockery::on(function (array $payload) use (&$submittedPayload) {
                    $submittedPayload = $payload;

                    return true;
                }))
                ->andReturn([
                    'success' => true,
                    'doc_entry' => 88,
                    'doc_num' => '12345',
                    'data' => ['DocEntry' => 88, 'DocNum' => 12345],
                ]);
        });

        $this->actingAs($user)
            ->postJson(route('utilities.ap-invoices.sap-payment.submit', ['utilityApInvoice' => $context['invoice']->id]), [
                'payment_means' => 'transfer',
                'account_id' => $context['account']->id,
                'payment_date' => '2026-09-18',
                'payment_amount' => 14007142,
                'prepared_by' => 'John Preparer',
                'approved_by' => 'Jane Approver',
            ]);

        $this->assertNotNull($submittedPayload);
        $this->assertSame($expectedRemarks, $submittedPayload['JournalRemarks']);
        $this->assertSame($submittedPayload['JournalRemarks'], $submittedPayload['Comments']);
    }

    public function test_ap_invoices_index_includes_pph23_withholding_ui_markup(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['akses_utilities', 'submit_sap_ap_invoice_utilities', 'submit_sap_utility_payment']);

        $this->actingAs($user)
            ->get(route('utilities.ap-invoices.index'))
            ->assertOk()
            ->assertSee('id="utilitySapPaymentWithholdingInfo"', false)
            ->assertSee('id="utilitySapPaymentWithholdingBreakdown"', false)
            ->assertSee('Total invoice (bruto)', false)
            ->assertSee('PPh23 (WTCode 1019)', false)
            ->assertSee('Dibayar netto', false)
            ->assertSee('renderUtilityWithholdingUi', false)
            ->assertSee('Invoice ini mengandung PPh23 sebesar', false);
    }

    /**
     * @return array{invoice: UtilityApInvoice, partner: SapBusinessPartner, account: Account}
     */
    protected function createPostedUtilityApInvoiceContext(): array
    {
        $partner = SapBusinessPartner::query()->create([
            'code' => 'VPLNPIDR01',
            'name' => 'PLN (Persero)',
            'type' => SapBusinessPartner::TYPE_SUPPLIER,
            'active' => true,
        ]);

        $invoice = UtilityApInvoice::factory()->posted()->create([
            'sap_business_partner_id' => $partner->id,
            'num_at_card' => 'PLN 8/26',
            'total_amount' => 14007142,
            'sap_doc_entry' => 2026,
            'sap_doc_num' => '9001',
        ]);

        $account = Account::query()->create([
            'account_number' => '11010001',
            'account_name' => 'BCA Operating',
            'type' => 'bank',
            'sap_account' => '11010001',
            'is_active' => true,
            'is_hidden' => false,
        ]);

        return [
            'invoice' => $invoice,
            'partner' => $partner,
            'account' => $account,
        ];
    }

    /**
     * @param  array{invoice: UtilityApInvoice, partner: SapBusinessPartner}  $context
     * @return array<string, mixed>
     */
    protected function openUtilityApInvoicePayload(array $context): array
    {
        return [
            'DocEntry' => $context['invoice']->sap_doc_entry,
            'DocNum' => (int) $context['invoice']->sap_doc_num,
            'CardCode' => $context['partner']->code,
            'DocumentStatus' => 'bost_Open',
            'Cancelled' => 'N',
            'DocTotal' => (float) $context['invoice']->total_amount,
            'PaidToDate' => 0,
        ];
    }

    protected function authorizedUser(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['akses_utilities', 'submit_sap_ap_invoice_utilities']);

        return $user;
    }

    /**
     * @return array{account: Account, partner: SapBusinessPartner, bill: UtilityBill}
     */
    protected function createEligibleContext(): array
    {
        $account = Account::query()->create([
            'account_number' => '61208001',
            'account_name' => 'Electricity',
            'type' => 'expense',
        ]);

        $partner = SapBusinessPartner::query()->create([
            'code' => 'VPLNPIDR01',
            'name' => 'PLN (Persero)',
            'type' => SapBusinessPartner::TYPE_SUPPLIER,
            'active' => true,
        ]);

        UtilityVendor::query()->where('jenis_utilitas', 'pln')->update([
            'sap_business_partner_id' => $partner->id,
        ]);

        return [
            'account' => $account,
            'partner' => $partner,
            'bill' => $this->createBill($account),
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    protected function createBill(Account $account, array $overrides = []): UtilityBill
    {
        $customer = UtilityCustomer::query()->create([
            'jenis_utilitas' => $overrides['jenis_utilitas'] ?? 'pln',
            'tipe' => 'postpaid',
            'id_pelanggan' => $overrides['id_pelanggan'] ?? '90060',
            'nama' => 'HO Office',
            'lokasi' => 'HO Office',
            'project' => '000H',
            'department' => '20',
            'account_id' => $account->id,
            'is_active' => true,
        ]);

        return UtilityBill::query()->create([
            'utility_customer_id' => $customer->id,
            'periode' => '2026-08',
            'jumlah_tagihan' => 14007142,
            'tanggal_jatuh_tempo' => '2026-08-31',
            'tanggal_bayar' => null,
        ]);
    }
}
