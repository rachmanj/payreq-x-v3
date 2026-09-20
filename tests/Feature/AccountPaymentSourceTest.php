<?php

namespace Tests\Feature;

use App\Http\Controllers\Cashier\PcbcController;
use App\Http\Controllers\CashierDashboardController;
use App\Models\Account;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AccountPaymentSourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'akses_utilities', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'submit_sap_utility_payment', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'create_general_op', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'cashier_dashboard', 'guard_name' => 'web']);

        Role::query()->firstOrCreate(['name' => 'cashier'], ['guard_name' => 'web']);
    }

    /**
     * @return array{petty: Account, prepaid: Account}
     */
    protected function seedProject000hCashAndPrepaidPaymentSource(): array
    {
        $petty = Account::query()->create([
            'account_number' => '11101001',
            'account_name' => 'Petty Cash HO',
            'type' => 'cash',
            'project' => '000H',
            'sap_account' => '11101001',
            'app_balance' => 10_000_000,
            'is_active' => true,
            'is_hidden' => false,
        ]);

        $prepaid = Account::query()->create([
            'account_number' => '13101020',
            'account_name' => 'Prepaid HO Clearing',
            'type' => 'asset',
            'project' => '000H',
            'sap_account' => '13101020',
            'app_balance' => 500_000_000,
            'is_payment_source' => true,
            'is_active' => true,
            'is_hidden' => false,
        ]);

        return ['petty' => $petty, 'prepaid' => $prepaid];
    }

    public function test_payment_source_account_appears_in_utility_ap_invoice_accounts(): void
    {
        $accounts = $this->seedProject000hCashAndPrepaidPaymentSource();

        $user = User::factory()->create(['project' => '000H']);
        $user->givePermissionTo(['akses_utilities', 'submit_sap_utility_payment']);

        $response = $this->actingAs($user)
            ->getJson(route('utilities.ap-invoices.accounts'));

        $response->assertOk();
        $ids = collect($response->json('accounts'))->pluck('id')->all();

        $this->assertContains($accounts['prepaid']->id, $ids);
        $this->assertContains($accounts['petty']->id, $ids);
    }

    public function test_payment_source_account_appears_on_general_op_create_form(): void
    {
        $accounts = $this->seedProject000hCashAndPrepaidPaymentSource();

        $department = Department::query()->create([
            'department_name' => 'Accounting',
            'akronim' => 'ACC',
            'sap_code' => '30',
        ]);

        $user = User::factory()->create([
            'project' => '000H',
            'department_id' => $department->id,
        ]);
        $user->givePermissionTo('create_general_op');

        $this->actingAs($user)
            ->get(route('cashier.general-op.create'))
            ->assertOk()
            ->assertSee('Prepaid HO Clearing', false)
            ->assertSee('Petty Cash HO', false);
    }

    public function test_payment_source_account_is_excluded_from_pure_cash_features(): void
    {
        $this->seedProject000hCashAndPrepaidPaymentSource();

        $user = User::factory()->create(['project' => '000H']);
        $user->assignRole('cashier');
        $user->givePermissionTo('cashier_dashboard');

        $this->actingAs($user);

        $dashboardBalance = app(CashierDashboardController::class)->dashboard_data()['today_pc_balance'];
        $this->assertSame(10_000_000, (int) $dashboardBalance);

        $cashOnlySum = (int) Account::query()->where('type', 'cash')->sum('app_balance');
        $this->assertSame(10_000_000, $cashOnlySum);

        $pcbcCreate = app(PcbcController::class)->create();
        $this->assertSame(10_000_000, (int) $pcbcCreate->getData()['defaultSystemAmount']);
    }

    public function test_lowest_id_cash_account_is_used_when_multiple_exist(): void
    {
        Account::query()->create([
            'account_number' => '11101001',
            'account_name' => 'Petty Cash HO',
            'type' => 'cash',
            'project' => '000H',
            'app_balance' => 1_000_000,
            'is_active' => true,
        ]);

        Account::query()->create([
            'account_number' => '11101020',
            'account_name' => 'PC Intransit',
            'type' => 'cash',
            'project' => '000H',
            'app_balance' => 99_000_000,
            'is_active' => true,
        ]);

        $user = User::factory()->create(['project' => '000H']);
        $user->givePermissionTo('cashier_dashboard');
        $this->actingAs($user);

        $balance = app(CashierDashboardController::class)->dashboard_data()['today_pc_balance'];
        $this->assertSame(1_000_000, (int) $balance);
    }

    public function test_is_payment_source_migration_is_idempotent(): void
    {
        $migration = include database_path('migrations/2026_09_20_095820_add_is_payment_source_to_accounts_table.php');

        Account::query()->create([
            'account_number' => '13101020',
            'account_name' => 'Prepaid HO',
            'type' => 'cash',
            'project' => '000H',
            'sap_account' => '13101020',
            'is_active' => true,
        ]);

        Account::query()->create([
            'account_number' => '13101021',
            'account_name' => 'Prepaid Site',
            'type' => 'expense',
            'project' => '001H',
            'is_active' => true,
        ]);

        $migration->up();
        $migration->up();

        $prepaidHo = Account::query()->where('account_number', '13101020')->first();
        $prepaidSite = Account::query()->where('account_number', '13101021')->first();

        $this->assertTrue($prepaidHo->is_payment_source);
        $this->assertSame('asset', $prepaidHo->type);
        $this->assertTrue($prepaidSite->is_payment_source);
        $this->assertSame('asset', $prepaidSite->type);
        $this->assertTrue(Schema::hasColumn('accounts', 'is_payment_source'));
    }
}
