<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Parameter;
use App\Models\SapBusinessPartner;
use App\Models\User;
use App\Services\SapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class InvoicePaymentAccountWhitelistTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.dds.api_url' => 'http://dds.test',
            'services.dds.api_key' => 'test-api-key',
            'services.dds.department_code' => '',
        ]);

        Permission::firstOrCreate(['name' => 'submit_sap_invoice_payment', 'guard_name' => 'web']);

        Parameter::query()
            ->where('name1', 'invoice_payment_accounts')
            ->delete();
    }

    public function test_eligible_payment_accounts_returns_whitelist_only_including_asset_type(): void
    {
        $this->seedWhitelistParameter('11101020,13101020');
        $this->seedWhitelistScenarioAccounts();

        $this->actingAs($this->authorizedUser())
            ->getJson(route('cashier.invoice-payment.sap-payment.preview', [
                'invoiceId' => 42,
                'invoice_number' => 'INV-001',
                'supplier_sap_code' => 'VSUP01',
                'amount' => 1500000,
                'payment_date' => '2026-08-20',
            ]))
            ->assertOk()
            ->assertJsonCount(2, 'accounts')
            ->assertJsonPath('accounts.0.account_number', '11101020')
            ->assertJsonPath('accounts.1.account_number', '13101020')
            ->assertJsonPath('accounts.1.type', 'asset');
    }

    public function test_eligible_payment_accounts_without_parameter_uses_legacy_cash_bank_and_payment_source(): void
    {
        Parameter::query()
            ->where('name1', 'invoice_payment_accounts')
            ->delete();

        $this->seedVendorPartner();

        Account::query()->create([
            'account_number' => '110101',
            'account_name' => 'Bank BCA HO',
            'type' => 'bank',
            'sap_account' => '11010101',
            'is_active' => true,
            'is_hidden' => false,
        ]);

        Account::query()->create([
            'account_number' => '13101020',
            'account_name' => 'Prepaid Expenses HO',
            'type' => 'asset',
            'sap_account' => '13101020',
            'is_active' => true,
            'is_hidden' => false,
            'is_payment_source' => true,
        ]);

        Account::query()->create([
            'account_number' => '99999999',
            'account_name' => 'Other Bank',
            'type' => 'bank',
            'sap_account' => '99999999',
            'is_active' => true,
            'is_hidden' => false,
        ]);

        $this->mock(SapService::class, function ($mock) {
            $mock->shouldReceive('getPurchaseInvoiceByNumAtCard')
                ->once()
                ->with('INV-001')
                ->andReturn($this->openApInvoice());
        });

        $response = $this->actingAs($this->authorizedUser())
            ->getJson(route('cashier.invoice-payment.sap-payment.preview', [
                'invoiceId' => 42,
                'invoice_number' => 'INV-001',
                'supplier_sap_code' => 'VSUP01',
                'amount' => 1500000,
                'payment_date' => '2026-08-20',
            ]))
            ->assertOk();

        $numbers = collect($response->json('accounts'))->pluck('account_number')->all();

        $this->assertSame(['110101', '99999999', '13101020'], $numbers);
    }

    public function test_submit_accepts_whitelisted_account_and_rejects_outside_whitelist(): void
    {
        $this->seedWhitelistParameter('11101020,13101020');
        $this->seedVendorPartner();

        $allowed = Account::query()->create([
            'account_number' => '11101020',
            'account_name' => 'PC In-Transit',
            'type' => 'cash',
            'sap_account' => '11101020',
            'is_active' => true,
            'is_hidden' => false,
        ]);

        Account::query()->create([
            'account_number' => '13101020',
            'account_name' => 'Prepaid Expenses HO',
            'type' => 'asset',
            'sap_account' => '13101020',
            'is_active' => true,
            'is_hidden' => false,
        ]);

        $otherBank = Account::query()->create([
            'account_number' => '99999999',
            'account_name' => 'Other Bank',
            'type' => 'bank',
            'sap_account' => '99999999',
            'is_active' => true,
            'is_hidden' => false,
        ]);

        $this->mock(SapService::class, function ($mock) {
            $mock->shouldReceive('getPurchaseInvoiceByNumAtCard')
                ->once()
                ->with('INV-001')
                ->andReturn($this->openApInvoice());

            $mock->shouldReceive('createOutgoingPayment')
                ->once()
                ->andReturn([
                    'success' => true,
                    'doc_entry' => 88,
                    'doc_num' => '12345',
                    'data' => ['DocEntry' => 88, 'DocNum' => 12345],
                ]);
        });

        Http::fake([
            'http://dds.test/*' => Http::response(['success' => true, 'data' => ['id' => 42]]),
        ]);

        $this->actingAs($this->authorizedUser())
            ->postJson(route('cashier.invoice-payment.sap-payment.submit', ['invoiceId' => 42]), [
                'invoice_number' => 'INV-001',
                'supplier_sap_code' => 'VSUP01',
                'amount' => 1500000,
                'payment_amount' => 1500000,
                'payment_date' => '2026-08-20',
                'payment_means' => 'transfer',
                'prepared_by' => 'John Preparer',
                'approved_by' => 'Jane Approver',
                'account_id' => $allowed->id,
                'close_invoice_in_dds' => false,
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->mock(SapService::class, function ($mock) {
            $mock->shouldReceive('getPurchaseInvoiceByNumAtCard')
                ->once()
                ->with('INV-001')
                ->andReturn($this->openApInvoice());
            $mock->shouldNotReceive('createOutgoingPayment');
        });

        $this->actingAs($this->authorizedUser())
            ->postJson(route('cashier.invoice-payment.sap-payment.submit', ['invoiceId' => 42]), [
                'invoice_number' => 'INV-001',
                'supplier_sap_code' => 'VSUP01',
                'amount' => 1500000,
                'payment_amount' => 1500000,
                'payment_date' => '2026-08-20',
                'payment_means' => 'transfer',
                'prepared_by' => 'John Preparer',
                'approved_by' => 'Jane Approver',
                'account_id' => $otherBank->id,
            ])
            ->assertStatus(422)
            ->assertJsonPath('error', 'Invalid account')
            ->assertJsonFragment([
                'message' => 'Akun yang dipilih tidak termasuk akun pembayaran yang diizinkan. Akun yang diizinkan: 11101020, 13101020.',
            ]);
    }

    public function test_migration_seeds_invoice_payment_accounts_parameter_idempotently(): void
    {
        Parameter::query()
            ->where('name1', 'invoice_payment_accounts')
            ->delete();

        $migration = include database_path('migrations/2026_09_21_070458_seed_invoice_payment_accounts_parameter.php');

        $migration->up();

        $this->assertDatabaseHas('parameters', [
            'name1' => 'invoice_payment_accounts',
            'name2' => 'ALL',
            'param_value' => '11101020,13101020',
        ]);

        Parameter::query()
            ->where('name1', 'invoice_payment_accounts')
            ->where('name2', 'ALL')
            ->update(['param_value' => '11101020']);

        $migration->up();

        $this->assertSame(
            1,
            Parameter::query()
                ->where('name1', 'invoice_payment_accounts')
                ->where('name2', 'ALL')
                ->count(),
        );

        $this->assertSame(
            '11101020',
            Parameter::query()
                ->where('name1', 'invoice_payment_accounts')
                ->where('name2', 'ALL')
                ->value('param_value'),
        );
    }

    protected function authorizedUser(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo('submit_sap_invoice_payment');

        return $user;
    }

    protected function seedWhitelistParameter(string $csv): void
    {
        Parameter::query()->updateOrCreate(
            ['name1' => 'invoice_payment_accounts', 'name2' => 'ALL'],
            ['param_value' => $csv],
        );
    }

    /**
     * @return array{pc_in_transit: Account, prepaid_ho: Account}
     */
    protected function seedWhitelistScenarioAccounts(): array
    {
        $this->seedVendorPartner();

        $pcInTransit = Account::query()->create([
            'account_number' => '11101020',
            'account_name' => 'PC In-Transit',
            'type' => 'cash',
            'sap_account' => '11101020',
            'is_active' => true,
            'is_hidden' => false,
        ]);

        $prepaidHo = Account::query()->create([
            'account_number' => '13101020',
            'account_name' => 'Prepaid Expenses HO',
            'type' => 'asset',
            'sap_account' => '13101020',
            'is_active' => true,
            'is_hidden' => false,
        ]);

        Account::query()->create([
            'account_number' => '99999999',
            'account_name' => 'Other Bank',
            'type' => 'bank',
            'sap_account' => '99999999',
            'is_active' => true,
            'is_hidden' => false,
        ]);

        $this->mock(SapService::class, function ($mock) {
            $mock->shouldReceive('getPurchaseInvoiceByNumAtCard')
                ->once()
                ->with('INV-001')
                ->andReturn($this->openApInvoice());
        });

        return [
            'pc_in_transit' => $pcInTransit,
            'prepaid_ho' => $prepaidHo,
        ];
    }

    protected function seedVendorPartner(): void
    {
        SapBusinessPartner::query()->create([
            'code' => 'VSUP01',
            'name' => 'PT Vendor Satu',
            'type' => SapBusinessPartner::TYPE_SUPPLIER,
            'active' => true,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function openApInvoice(): array
    {
        return [
            'DocEntry' => 555,
            'DocNum' => 9001,
            'CardCode' => 'VSUP01',
            'DocumentStatus' => 'bost_Open',
            'Cancelled' => 'N',
            'NumAtCard' => 'INV-001',
            'DocTotal' => 1500000,
            'PaidToDate' => 0,
        ];
    }
}
