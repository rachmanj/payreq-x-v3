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
