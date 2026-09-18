<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Parameter;
use App\Models\User;
use App\Services\SapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CashierDashboardClearingAccountTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(
            ['name' => 'cashier_dashboard'],
            ['guard_name' => 'web'],
        );
    }

    protected function createCashierUser(): User
    {
        $user = User::factory()->create(['project' => '000H']);
        $user->givePermissionTo('cashier_dashboard');

        return $user;
    }

    protected function seedClearingAccountsParameter(string $value = '13101020,13101021'): void
    {
        Parameter::query()->create([
            'name1' => 'dashboard_clearing_accounts',
            'name2' => 'ALL',
            'param_value' => $value,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function sampleStatement(string $code, string $name, string $start, string $end): array
    {
        return [
            'account' => [
                'id' => 1,
                'code' => $code,
                'name' => $name,
                'account_type' => 'ASSET',
            ],
            'start_date' => $start,
            'end_date' => $end,
            'opening_balance' => 1000000.0,
            'closing_balance' => 1500000.0,
            'transactions' => [
                [
                    'id' => 1,
                    'posting_date' => $end,
                    'doc_num' => '1001',
                    'doc_type' => '30',
                    'tx_num' => '5001',
                    'description' => 'Clearing test',
                    'debit_amount' => 500000.0,
                    'credit_amount' => 0.0,
                    'project_code' => '000H',
                    'running_balance' => 1500000.0,
                ],
            ],
            'summary' => [
                'total_debit' => 500000.0,
                'total_credit' => 0.0,
                'transaction_count' => 1,
            ],
        ];
    }

    protected function seedCashAccount(string $project = '000H'): void
    {
        Account::query()->create([
            'type' => 'cash',
            'account_number' => '11010101',
            'account_name' => 'Cash Account',
            'project' => $project,
            'app_balance' => 10000000,
            'is_active' => true,
        ]);
    }

    public function test_cashier_dashboard_shows_section_d_with_two_clearing_cards(): void
    {
        $this->seedClearingAccountsParameter();
        $this->seedCashAccount();
        $user = $this->createCashierUser();
        $today = now()->format('Y-m-d');

        $this->mock(SapService::class, function ($mock) use ($today) {
            $mock->shouldReceive('getAccountStatement')
                ->once()
                ->with('13101020', $today, $today)
                ->andReturn($this->sampleStatement('13101020', 'Clearing A', $today, $today));

            $mock->shouldReceive('getAccountStatement')
                ->once()
                ->with('13101021', $today, $today)
                ->andReturn($this->sampleStatement('13101021', 'Clearing B', $today, $today));
        });

        $response = $this->actingAs($user)->get(route('cashier.dashboard.index'));

        $response->assertOk()
            ->assertSee('Section D — Clearing Account (SAP)', false)
            ->assertSee('13101020', false)
            ->assertSee('13101021', false)
            ->assertSee('Clearing A', false)
            ->assertSee('Clearing B', false);
    }

    public function test_cashier_clearing_transactions_returns_datatables_shape(): void
    {
        $this->seedClearingAccountsParameter();
        $user = $this->createCashierUser();

        $this->mock(SapService::class, function ($mock) {
            $mock->shouldReceive('getAccountStatement')
                ->once()
                ->with('13101020', '2026-01-01', '2026-01-31')
                ->andReturn($this->sampleStatement('13101020', 'Clearing A', '2026-01-01', '2026-01-31'));
        });

        $response = $this->actingAs($user)->getJson(route('cashier.dashboard.clearing.transactions', [
            'account_code' => '13101020',
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-31',
            'draw' => 1,
        ]));

        $response->assertOk()
            ->assertJsonPath('opening_balance', 1000000)
            ->assertJsonPath('closing_balance', 1500000)
            ->assertJsonPath('summary.transaction_count', 1)
            ->assertJsonPath('data.0.tx_num', '5001')
            ->assertJsonPath('account.code', '13101020');
    }

    public function test_cashier_clearing_transactions_denied_without_permission(): void
    {
        $this->seedClearingAccountsParameter();
        $user = User::factory()->create(['project' => '000H']);

        $this->mock(SapService::class, function ($mock) {
            $mock->shouldNotReceive('getAccountStatement');
        });

        $this->actingAs($user)->getJson(route('cashier.dashboard.clearing.transactions', [
            'account_code' => '13101020',
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-31',
            'draw' => 1,
        ]))->assertForbidden();
    }

    public function test_cashier_clearing_transactions_rejects_unmonitored_account(): void
    {
        $this->seedClearingAccountsParameter();
        $user = $this->createCashierUser();

        $this->mock(SapService::class, function ($mock) {
            $mock->shouldNotReceive('getAccountStatement');
        });

        $this->actingAs($user)->getJson(route('cashier.dashboard.clearing.transactions', [
            'account_code' => '99999999',
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-31',
            'draw' => 1,
        ]))->assertStatus(422)
            ->assertJsonValidationErrors(['account_code']);
    }

    public function test_cashier_clearing_transactions_rejects_range_over_six_months(): void
    {
        $this->seedClearingAccountsParameter();
        $user = $this->createCashierUser();

        $this->mock(SapService::class, function ($mock) {
            $mock->shouldNotReceive('getAccountStatement');
        });

        $this->actingAs($user)->getJson(route('cashier.dashboard.clearing.transactions', [
            'account_code' => '13101020',
            'start_date' => '2026-01-01',
            'end_date' => '2026-08-01',
            'draw' => 1,
        ]))->assertStatus(422)
            ->assertJsonValidationErrors(['end_date']);
    }
}
