<?php

namespace Tests\Feature;

use App\Jobs\FetchSapGlLinesJob;
use App\Models\Account;
use App\Models\BankReconciliation;
use App\Models\Giro;
use App\Models\SapGlLine;
use App\Models\User;
use App\Services\SapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SapAccountStatementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::query()->firstOrCreate(['name' => 'cashier'], ['guard_name' => 'web']);
    }

    protected function createCashier(): User
    {
        $user = User::factory()->create(['project' => '000H']);
        $user->assignRole('cashier');

        return $user;
    }

    protected function createBankAccount(string $project = '000H'): Account
    {
        return Account::query()->create([
            'account_number' => '11501004',
            'account_name' => 'Bank BCA Test',
            'type' => 'bank',
            'project' => $project,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function sampleStatement(): array
    {
        return [
            'account' => [
                'id' => 1,
                'code' => '11501004',
                'name' => 'Bank BCA Test',
                'account_type' => 'ASSET',
            ],
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-31',
            'opening_balance' => 1000.0,
            'closing_balance' => 1500.0,
            'transactions' => [
                [
                    'id' => 1,
                    'posting_date' => '2026-01-15',
                    'doc_num' => '1001',
                    'doc_type' => '30',
                    'tx_num' => '5001',
                    'description' => 'Test transfer',
                    'debit_amount' => 500.0,
                    'credit_amount' => 0.0,
                    'project_code' => '000H',
                    'department_name' => null,
                    'unit_no' => null,
                    'running_balance' => 1500.0,
                ],
            ],
            'summary' => [
                'total_debit' => 500.0,
                'total_credit' => 0.0,
                'transaction_count' => 1,
            ],
        ];
    }

    public function test_sap_transactions_data_returns_statement_from_sap_service(): void
    {
        $user = $this->createCashier();
        $this->createBankAccount();

        $this->mock(SapService::class, function ($mock) {
            $mock->shouldReceive('getAccountStatement')
                ->once()
                ->with('11501004', '2026-01-01', '2026-01-31')
                ->andReturn($this->sampleStatement());
        });

        $response = $this->actingAs($user)->postJson(route('cashier.sap-transactions.data'), [
            'account_code' => '11501004',
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-31',
            'draw' => 1,
        ]);

        $response->assertOk()
            ->assertJsonPath('opening_balance', 1000)
            ->assertJsonPath('closing_balance', 1500)
            ->assertJsonPath('summary.transaction_count', 1)
            ->assertJsonPath('data.0.tx_num', '5001')
            ->assertJsonPath('account.code', '11501004');
    }

    public function test_sap_transactions_data_rejects_range_over_six_months(): void
    {
        $user = $this->createCashier();
        $this->createBankAccount();

        $this->mock(SapService::class, function ($mock) {
            $mock->shouldNotReceive('getAccountStatement');
        });

        $this->actingAs($user)->postJson(route('cashier.sap-transactions.data'), [
            'account_code' => '11501004',
            'start_date' => '2026-01-01',
            'end_date' => '2026-08-01',
            'draw' => 1,
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['end_date']);
    }

    public function test_fetch_sap_gl_lines_job_persists_statement_lines_and_balances(): void
    {
        $user = $this->createCashier();

        $bankId = DB::table('banks')->insertGetId([
            'name' => 'BCA',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $giro = Giro::query()->create([
            'acc_no' => '1234567890',
            'acc_name' => 'Test Account',
            'bank_id' => $bankId,
            'project' => '000H',
            'sap_account' => '11501004',
        ]);

        $reconciliation = BankReconciliation::query()->create([
            'giro_id' => $giro->id,
            'periode' => '2026-01-01',
            'source_mode' => BankReconciliation::SOURCE_MANUAL,
            'status' => BankReconciliation::STATUS_IN_REVIEW,
            'created_by' => $user->id,
        ]);

        $this->mock(SapService::class, function ($mock) {
            $mock->shouldReceive('getAccountStatement')
                ->once()
                ->with('11501004', '2026-01-01', '2026-01-31')
                ->andReturn($this->sampleStatement());
        });

        (new FetchSapGlLinesJob($reconciliation->id))->handle(
            app(SapService::class),
            app(\App\Services\ReconciliationMatchingService::class)
        );

        $reconciliation->refresh();

        $this->assertSame('1000.00', (string) $reconciliation->opening_balance_book);
        $this->assertSame('1500.00', (string) $reconciliation->closing_balance_book);
        $this->assertDatabaseHas('sap_gl_lines', [
            'bank_reconciliation_id' => $reconciliation->id,
            'transaction_id' => '5001',
            'doc_num' => '1001',
            'matched_status' => SapGlLine::MATCH_UNMATCHED,
        ]);
    }
}
