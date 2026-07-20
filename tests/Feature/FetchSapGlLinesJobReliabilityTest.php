<?php

namespace Tests\Feature;

use App\Jobs\FetchSapGlLinesJob;
use App\Models\BankReconciliation;
use App\Models\BankStatementLine;
use App\Models\Giro;
use App\Models\MatchGroupBankLine;
use App\Models\MatchGroupSapLine;
use App\Models\ReconciliationMatchGroup;
use App\Models\SapGlLine;
use App\Models\User;
use App\Services\SapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FetchSapGlLinesJobReliabilityTest extends TestCase
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

    protected function createGiro(?string $sapAccount = '11501004'): Giro
    {
        $bankId = DB::table('banks')->insertGetId([
            'name' => 'BCA',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Giro::query()->create([
            'acc_no' => '1234567890',
            'acc_name' => 'Test Account',
            'bank_id' => $bankId,
            'project' => '000H',
            'sap_account' => $sapAccount,
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

    public function test_sap_failure_preserves_existing_lines_and_marks_failed(): void
    {
        $user = $this->createCashier();
        $giro = $this->createGiro();

        $reconciliation = BankReconciliation::query()->create([
            'giro_id' => $giro->id,
            'periode' => '2026-01-01',
            'source_mode' => BankReconciliation::SOURCE_MANUAL,
            'status' => BankReconciliation::STATUS_IN_REVIEW,
            'created_by' => $user->id,
        ]);

        $existing = SapGlLine::query()->create([
            'bank_reconciliation_id' => $reconciliation->id,
            'posting_date' => '2026-01-10',
            'doc_num' => 'OLD-1',
            'debit' => '100.00',
            'credit' => '0.00',
            'matched_status' => SapGlLine::MATCH_UNMATCHED,
        ]);

        $this->mock(SapService::class, function ($mock) {
            $mock->shouldReceive('getAccountStatement')
                ->once()
                ->andThrow(new RuntimeException('SAP connection refused'));
        });

        try {
            (new FetchSapGlLinesJob($reconciliation->id))->handle(
                app(SapService::class),
                app(\App\Services\ReconciliationMatchingService::class)
            );
            $this->fail('Expected RuntimeException was not thrown');
        } catch (RuntimeException $exception) {
            $this->assertSame('SAP connection refused', $exception->getMessage());
        }

        $reconciliation->refresh();
        $this->assertSame(BankReconciliation::STATUS_FAILED, $reconciliation->status);
        $this->assertStringContainsString('SAP GL fetch failed', (string) $reconciliation->notes);
        $this->assertDatabaseHas('sap_gl_lines', ['id' => $existing->id, 'doc_num' => 'OLD-1']);
        $this->assertSame(1, $reconciliation->sapGlLines()->count());
    }

    public function test_missing_sap_account_marks_failed_without_wiping_lines(): void
    {
        $user = $this->createCashier();
        $giro = $this->createGiro(null);

        $reconciliation = BankReconciliation::query()->create([
            'giro_id' => $giro->id,
            'periode' => '2026-01-01',
            'source_mode' => BankReconciliation::SOURCE_MANUAL,
            'status' => BankReconciliation::STATUS_IN_REVIEW,
            'created_by' => $user->id,
        ]);

        SapGlLine::query()->create([
            'bank_reconciliation_id' => $reconciliation->id,
            'posting_date' => '2026-01-10',
            'doc_num' => 'KEEP-ME',
            'debit' => '50.00',
            'credit' => '0.00',
            'matched_status' => SapGlLine::MATCH_UNMATCHED,
        ]);

        $this->mock(SapService::class, function ($mock) {
            $mock->shouldNotReceive('getAccountStatement');
        });

        (new FetchSapGlLinesJob($reconciliation->id))->handle(
            app(SapService::class),
            app(\App\Services\ReconciliationMatchingService::class)
        );

        $reconciliation->refresh();
        $this->assertSame(BankReconciliation::STATUS_FAILED, $reconciliation->status);
        $this->assertStringContainsString('SAP account not configured', (string) $reconciliation->notes);
        $this->assertDatabaseHas('sap_gl_lines', [
            'bank_reconciliation_id' => $reconciliation->id,
            'doc_num' => 'KEEP-ME',
        ]);
    }

    public function test_successful_fetch_replaces_lines_and_clears_match_groups(): void
    {
        $user = $this->createCashier();
        $giro = $this->createGiro();

        $reconciliation = BankReconciliation::query()->create([
            'giro_id' => $giro->id,
            'periode' => '2026-01-01',
            'source_mode' => BankReconciliation::SOURCE_MANUAL,
            'status' => BankReconciliation::STATUS_FAILED,
            'notes' => 'previous error',
            'created_by' => $user->id,
        ]);

        $bankLine = BankStatementLine::query()->create([
            'bank_reconciliation_id' => $reconciliation->id,
            'transaction_date' => '2026-01-15',
            'debit' => '0.00',
            'credit' => '500.00',
            'matched_status' => BankStatementLine::MATCH_MATCHED,
            'line_order' => 1,
            'is_ai_extracted' => false,
        ]);

        $oldSap = SapGlLine::query()->create([
            'bank_reconciliation_id' => $reconciliation->id,
            'posting_date' => '2026-01-15',
            'doc_num' => 'OLD',
            'debit' => '500.00',
            'credit' => '0.00',
            'matched_status' => SapGlLine::MATCH_MATCHED,
        ]);

        $group = ReconciliationMatchGroup::query()->create([
            'bank_reconciliation_id' => $reconciliation->id,
            'match_type' => ReconciliationMatchGroup::TYPE_MANUAL,
            'confidence_score' => 1,
            'bank_total' => '-500.00',
            'sap_total' => '500.00',
            'difference' => '0.00',
            'created_by' => $user->id,
        ]);

        MatchGroupBankLine::query()->create([
            'reconciliation_match_group_id' => $group->id,
            'bank_statement_line_id' => $bankLine->id,
        ]);

        MatchGroupSapLine::query()->create([
            'reconciliation_match_group_id' => $group->id,
            'sap_gl_line_id' => $oldSap->id,
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
        $this->assertSame(BankReconciliation::STATUS_IN_REVIEW, $reconciliation->status);
        $this->assertNull($reconciliation->notes);
        $this->assertSame('1000.00', (string) $reconciliation->opening_balance_book);
        $this->assertSame('1500.00', (string) $reconciliation->closing_balance_book);
        $this->assertDatabaseMissing('sap_gl_lines', ['id' => $oldSap->id]);
        $this->assertDatabaseMissing('reconciliation_match_groups', ['id' => $group->id]);
        $this->assertSame(BankStatementLine::MATCH_UNMATCHED, $bankLine->fresh()->matched_status);
        $this->assertDatabaseHas('sap_gl_lines', [
            'bank_reconciliation_id' => $reconciliation->id,
            'transaction_id' => '5001',
            'matched_status' => SapGlLine::MATCH_UNMATCHED,
        ]);
    }
}
