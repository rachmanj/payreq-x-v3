<?php

namespace Tests\Feature;

use App\Models\Parameter;
use App\Models\User;
use App\Services\ClearingAccountMonitorService;
use App\Services\SapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AccountingManagerDashboardClearingAccountTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(
            ['name' => 'view_accounting_manager_dashboard'],
            ['guard_name' => 'web'],
        );
    }

    protected function createDashboardUser(): User
    {
        $user = User::factory()->create(['project' => '000H']);
        $user->givePermissionTo('view_accounting_manager_dashboard');

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

    public function test_migration_seeds_dashboard_clearing_accounts_parameter_idempotently(): void
    {
        Artisan::call('migrate', [
            '--path' => 'database/migrations/2026_09_18_013908_seed_dashboard_clearing_accounts_parameter.php',
            '--force' => true,
        ]);

        $this->assertDatabaseHas('parameters', [
            'name1' => 'dashboard_clearing_accounts',
            'name2' => 'ALL',
            'param_value' => '13101020,13101021',
        ]);

        Artisan::call('migrate', [
            '--path' => 'database/migrations/2026_09_18_013908_seed_dashboard_clearing_accounts_parameter.php',
            '--force' => true,
        ]);

        $this->assertSame(
            1,
            Parameter::query()
                ->where('name1', 'dashboard_clearing_accounts')
                ->where('name2', 'ALL')
                ->count(),
        );
    }

    public function test_clearing_account_codes_are_parsed_from_csv_with_trim_and_unique(): void
    {
        $service = app(ClearingAccountMonitorService::class);

        $codes = $service->parseAccountCodes(' 13101020 , 13101021,13101020, , ');

        $this->assertSame(['13101020', '13101021'], $codes);
    }

    public function test_dashboard_shows_section_d_with_two_clearing_cards(): void
    {
        $this->seedClearingAccountsParameter();
        $user = $this->createDashboardUser();
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

        $response = $this->actingAs($user)->get(route('accounting.manager-dashboard.index'));

        $response->assertOk()
            ->assertSee('Section D — Clearing Account (SAP)', false)
            ->assertSee('13101020', false)
            ->assertSee('13101021', false)
            ->assertSee('Clearing A', false)
            ->assertSee('Clearing B', false);
    }

    public function test_dashboard_shows_empty_message_when_parameter_missing(): void
    {
        Parameter::query()
            ->where('name1', 'dashboard_clearing_accounts')
            ->delete();

        $user = $this->createDashboardUser();

        $this->mock(SapService::class, function ($mock) {
            $mock->shouldNotReceive('getAccountStatement');
        });

        $response = $this->actingAs($user)->get(route('accounting.manager-dashboard.index'));

        $response->assertOk()
            ->assertSee('Belum ada akun clearing yang dipantau', false);
    }

    public function test_dashboard_returns_200_when_one_clearing_account_sap_call_fails(): void
    {
        $this->seedClearingAccountsParameter();
        $user = $this->createDashboardUser();
        $today = now()->format('Y-m-d');

        $this->mock(SapService::class, function ($mock) use ($today) {
            $mock->shouldReceive('getAccountStatement')
                ->once()
                ->with('13101020', $today, $today)
                ->andReturn($this->sampleStatement('13101020', 'Clearing A', $today, $today));

            $mock->shouldReceive('getAccountStatement')
                ->once()
                ->with('13101021', $today, $today)
                ->andThrow(new \RuntimeException('SAP timeout'));
        });

        $response = $this->actingAs($user)->get(route('accounting.manager-dashboard.index'));

        $response->assertOk()
            ->assertSee('13101020', false)
            ->assertSee('13101021', false)
            ->assertSee('SAP timeout', false);
    }

    public function test_clearing_transactions_returns_datatables_shape_for_monitored_account(): void
    {
        $this->seedClearingAccountsParameter();
        $user = $this->createDashboardUser();

        $this->mock(SapService::class, function ($mock) {
            $mock->shouldReceive('getAccountStatement')
                ->once()
                ->with('13101020', '2026-01-01', '2026-01-31')
                ->andReturn($this->sampleStatement('13101020', 'Clearing A', '2026-01-01', '2026-01-31'));
        });

        $response = $this->actingAs($user)->getJson(route('accounting.manager-dashboard.clearing.transactions', [
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

    public function test_clearing_transactions_rejects_unmonitored_account(): void
    {
        $this->seedClearingAccountsParameter();
        $user = $this->createDashboardUser();

        $this->mock(SapService::class, function ($mock) {
            $mock->shouldNotReceive('getAccountStatement');
        });

        $this->actingAs($user)->getJson(route('accounting.manager-dashboard.clearing.transactions', [
            'account_code' => '99999999',
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-31',
            'draw' => 1,
        ]))->assertStatus(422)
            ->assertJsonValidationErrors(['account_code']);
    }

    public function test_clearing_transactions_rejects_range_over_six_months(): void
    {
        $this->seedClearingAccountsParameter();
        $user = $this->createDashboardUser();

        $this->mock(SapService::class, function ($mock) {
            $mock->shouldNotReceive('getAccountStatement');
        });

        $this->actingAs($user)->getJson(route('accounting.manager-dashboard.clearing.transactions', [
            'account_code' => '13101020',
            'start_date' => '2026-01-01',
            'end_date' => '2026-08-01',
            'draw' => 1,
        ]))->assertStatus(422)
            ->assertJsonValidationErrors(['end_date']);
    }

    public function test_clearing_transactions_denied_without_permission(): void
    {
        $this->seedClearingAccountsParameter();
        $user = User::factory()->create(['project' => '000H']);

        $this->mock(SapService::class, function ($mock) {
            $mock->shouldNotReceive('getAccountStatement');
        });

        $this->actingAs($user)->getJson(route('accounting.manager-dashboard.clearing.transactions', [
            'account_code' => '13101020',
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-31',
            'draw' => 1,
        ]))->assertForbidden();
    }
}
