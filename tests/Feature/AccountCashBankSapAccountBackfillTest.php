<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Parameter;
use App\Models\SapBusinessPartner;
use App\Models\User;
use App\Services\SapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AccountCashBankSapAccountBackfillTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'submit_sap_invoice_payment', 'guard_name' => 'web']);
    }

    /**
     * @return object{up: callable, down: callable}
     */
    protected function migration(): object
    {
        return include database_path('migrations/2026_09_21_065834_backfill_sap_account_for_cash_and_bank_accounts.php');
    }

    public function test_backfill_sets_sap_account_for_empty_cash_and_bank_with_numeric_account_number(): void
    {
        $migration = $this->migration();

        Account::query()->create([
            'account_number' => '11201020',
            'account_name' => 'BCA IDR',
            'type' => 'bank',
            'project' => '000H',
            'sap_account' => null,
            'is_active' => true,
            'is_hidden' => false,
        ]);

        Account::query()->create([
            'account_number' => '11101001',
            'account_name' => 'Petty Cash HO',
            'type' => 'cash',
            'project' => '000H',
            'sap_account' => '11101001',
            'is_active' => true,
            'is_hidden' => false,
        ]);

        Account::query()->create([
            'account_number' => '110101',
            'account_name' => 'Bank with custom SAP mapping',
            'type' => 'bank',
            'project' => '000H',
            'sap_account' => '11010101',
            'is_active' => true,
            'is_hidden' => false,
        ]);

        Account::query()->create([
            'account_number' => 'CASH-LEGACY',
            'account_name' => 'Legacy cash code',
            'type' => 'cash',
            'project' => '000H',
            'sap_account' => null,
            'is_active' => true,
            'is_hidden' => false,
        ]);

        Account::query()->create([
            'account_number' => '21101001',
            'account_name' => 'AP Control',
            'type' => 'liability',
            'project' => '000H',
            'sap_account' => null,
            'is_active' => true,
            'is_hidden' => false,
        ]);

        $migration->up();

        $bca = Account::query()->where('account_number', '11201020')->first();
        $petty = Account::query()->where('account_number', '11101001')->first();
        $customMapped = Account::query()->where('account_number', '110101')->first();
        $legacy = Account::query()->where('account_number', 'CASH-LEGACY')->first();
        $liability = Account::query()->where('account_number', '21101001')->first();

        $this->assertSame('11201020', $bca->sap_account);
        $this->assertSame('11101001', $petty->sap_account);
        $this->assertSame('11010101', $customMapped->sap_account);
        $this->assertNull($legacy->sap_account);
        $this->assertNull($liability->sap_account);
    }

    public function test_backfill_sap_account_migration_is_idempotent(): void
    {
        $migration = $this->migration();

        Account::query()->create([
            'account_number' => '11201005',
            'account_name' => 'Mandiri IDR 021C',
            'type' => 'bank',
            'project' => '021C',
            'sap_account' => null,
            'is_active' => true,
            'is_hidden' => false,
        ]);

        $migration->up();
        $migration->up();

        $mandiri = Account::query()->where('account_number', '11201005')->first();

        $this->assertSame('11201005', $mandiri->sap_account);
        $this->assertSame('bank', $mandiri->type);
        $this->assertSame('021C', $mandiri->project);
    }

    public function test_backfill_down_clears_only_auto_mapped_sap_accounts(): void
    {
        $migration = $this->migration();

        Account::query()->create([
            'account_number' => '11201061',
            'account_name' => 'Niaga GBP',
            'type' => 'bank',
            'project' => '000H',
            'sap_account' => null,
            'is_active' => true,
            'is_hidden' => false,
        ]);

        Account::query()->create([
            'account_number' => '110101',
            'account_name' => 'Bank custom SAP',
            'type' => 'bank',
            'project' => '000H',
            'sap_account' => '11010101',
            'is_active' => true,
            'is_hidden' => false,
        ]);

        $migration->up();
        $migration->down();

        $niaga = Account::query()->where('account_number', '11201061')->first();
        $customMapped = Account::query()->where('account_number', '110101')->first();

        $this->assertNull($niaga->sap_account);
        $this->assertSame('11010101', $customMapped->sap_account);
    }

    public function test_invoice_payment_preview_lists_bank_account_after_sap_account_backfill(): void
    {
        $migration = $this->migration();

        SapBusinessPartner::query()->create([
            'code' => 'VSUP01',
            'name' => 'PT Vendor Satu',
            'type' => SapBusinessPartner::TYPE_SUPPLIER,
            'active' => true,
        ]);

        Account::query()->create([
            'account_number' => '11201020',
            'account_name' => 'BCA IDR',
            'type' => 'bank',
            'project' => '000H',
            'sap_account' => null,
            'is_active' => true,
            'is_hidden' => false,
        ]);

        $migration->up();

        // Sejak 2026-09-21 daftar akun pembayaran invoice dibatasi parameter whitelist
        // 'invoice_payment_accounts'. Test ini menguji jalur backfill sap_account (perilaku lama),
        // jadi kosongkan whitelist dulu supaya daftar akun kembali memakai aturan type/sap_account.
        Parameter::query()->where('name1', 'invoice_payment_accounts')->delete();

        $this->mock(SapService::class, function ($mock) {
            $mock->shouldReceive('getPurchaseInvoiceByNumAtCard')
                ->once()
                ->with('INV-001')
                ->andReturn([
                    'DocEntry' => 555,
                    'DocNum' => 9001,
                    'CardCode' => 'VSUP01',
                    'DocumentStatus' => 'bost_Open',
                    'Cancelled' => 'N',
                    'NumAtCard' => 'INV-001',
                    'DocTotal' => 1500000,
                    'PaidToDate' => 0,
                ]);
        });

        $user = User::factory()->create();
        $user->givePermissionTo('submit_sap_invoice_payment');

        $this->actingAs($user)
            ->getJson(route('cashier.invoice-payment.sap-payment.preview', [
                'invoiceId' => 42,
                'invoice_number' => 'INV-001',
                'supplier_sap_code' => 'VSUP01',
                'amount' => 1500000,
                'payment_date' => '2026-08-20',
            ]))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment([
                'sap_account' => '11201020',
                'account_number' => '11201020',
            ]);
    }
}
