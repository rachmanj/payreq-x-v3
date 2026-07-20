<?php

namespace Tests\Feature;

use App\Models\BankReconciliation;
use App\Models\BankStatementLine;
use App\Models\Giro;
use App\Models\SapGlLine;
use App\Models\User;
use App\Services\ReconciliationBalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReconciliationStatementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::query()->firstOrCreate(['name' => 'cashier'], ['guard_name' => 'web']);
    }

    protected function createPreparer(): User
    {
        $user = User::factory()->create(['project' => '000H']);
        $user->assignRole('cashier');

        return $user;
    }

    protected function createGiro(): Giro
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
        ]);
    }

    protected function createReconciliation(User $preparer): BankReconciliation
    {
        return BankReconciliation::query()->create([
            'giro_id' => $this->createGiro()->id,
            'periode' => '2026-02-01',
            'source_mode' => BankReconciliation::SOURCE_MANUAL,
            'status' => BankReconciliation::STATUS_IN_REVIEW,
            'created_by' => $preparer->id,
            'opening_balance_bank' => '10000.00',
            'closing_balance_bank' => '10000.00',
            'opening_balance_book' => '10000.00',
            'closing_balance_book' => '10000.00',
        ]);
    }

    public function test_deposit_in_transit_reconciles_when_book_closing_is_higher(): void
    {
        $preparer = $this->createPreparer();
        $reconciliation = $this->createReconciliation($preparer);
        $reconciliation->update([
            'closing_balance_bank' => '10000.00',
            'closing_balance_book' => '10500.00',
        ]);

        SapGlLine::query()->create([
            'bank_reconciliation_id' => $reconciliation->id,
            'posting_date' => '2026-02-28',
            'description' => 'Deposit not yet cleared',
            'debit' => '500.00',
            'credit' => '0.00',
            'matched_status' => SapGlLine::MATCH_UNMATCHED,
        ]);

        $statement = app(ReconciliationBalanceService::class)->reconciliationStatement($reconciliation);

        $this->assertFalse($statement['incomplete']);
        $this->assertTrue($statement['is_reconciled']);
        $this->assertSame(10500.0, $statement['adjusted_bank']);
        $this->assertSame(10500.0, $statement['adjusted_book']);
        $this->assertSame(0.0, $statement['unexplained_difference']);
        $this->assertArrayHasKey(SapGlLine::TYPE_DEPOSIT_IN_TRANSIT, $statement['book_items']);
    }

    public function test_outstanding_payment_reconciles_when_book_closing_is_lower(): void
    {
        $preparer = $this->createPreparer();
        $reconciliation = $this->createReconciliation($preparer);
        $reconciliation->update([
            'closing_balance_bank' => '10000.00',
            'closing_balance_book' => '9700.00',
        ]);

        SapGlLine::query()->create([
            'bank_reconciliation_id' => $reconciliation->id,
            'posting_date' => '2026-02-27',
            'description' => 'Cheque outstanding',
            'debit' => '0.00',
            'credit' => '300.00',
            'matched_status' => SapGlLine::MATCH_UNMATCHED,
        ]);

        $statement = app(ReconciliationBalanceService::class)->reconciliationStatement($reconciliation);

        $this->assertTrue($statement['is_reconciled']);
        $this->assertSame(9700.0, $statement['adjusted_bank']);
        $this->assertSame(9700.0, $statement['adjusted_book']);
        $this->assertArrayHasKey(SapGlLine::TYPE_OUTSTANDING_PAYMENT, $statement['book_items']);
    }

    public function test_bank_charge_not_booked_reconciles(): void
    {
        $preparer = $this->createPreparer();
        $reconciliation = $this->createReconciliation($preparer);
        $reconciliation->update([
            'closing_balance_bank' => '9975.00',
            'closing_balance_book' => '10000.00',
        ]);

        BankStatementLine::query()->create([
            'bank_reconciliation_id' => $reconciliation->id,
            'transaction_date' => '2026-02-15',
            'description' => 'Admin fee',
            'debit' => '25.00',
            'credit' => '0.00',
            'matched_status' => BankStatementLine::MATCH_UNMATCHED,
            'line_order' => 1,
            'is_ai_extracted' => false,
        ]);

        $statement = app(ReconciliationBalanceService::class)->reconciliationStatement($reconciliation);

        // adjusted_bank = 9975 + 0 = 9975
        // adjusted_book = 10000 - 25 = 9975
        $this->assertTrue($statement['is_reconciled']);
        $this->assertSame(9975.0, $statement['adjusted_bank']);
        $this->assertSame(9975.0, $statement['adjusted_book']);
        $this->assertArrayHasKey(BankStatementLine::TYPE_CHARGE_NOT_BOOKED, $statement['bank_items']);
    }

    public function test_opening_discrepancy_is_surfaced(): void
    {
        $preparer = $this->createPreparer();
        $reconciliation = $this->createReconciliation($preparer);
        $reconciliation->update([
            'opening_balance_bank' => '10000.00',
            'opening_balance_book' => '9900.00',
            'closing_balance_bank' => '10000.00',
            'closing_balance_book' => '9900.00',
        ]);

        $statement = app(ReconciliationBalanceService::class)->reconciliationStatement($reconciliation);

        $this->assertSame(100.0, $statement['opening_discrepancy']);
        $this->assertFalse($statement['is_reconciled']);
        $this->assertNotNull($statement['diagnostic']);
        $this->assertStringContainsString('Opening balances also differ', (string) $statement['diagnostic']);
    }

    public function test_null_closing_balances_mark_statement_incomplete(): void
    {
        $preparer = $this->createPreparer();
        $reconciliation = $this->createReconciliation($preparer);
        $reconciliation->update([
            'closing_balance_bank' => null,
            'closing_balance_book' => null,
        ]);

        $statement = app(ReconciliationBalanceService::class)->reconciliationStatement($reconciliation);

        $this->assertTrue($statement['incomplete']);
        $this->assertFalse($statement['is_reconciled']);
        $this->assertNull($statement['adjusted_bank']);
        $this->assertStringContainsString('Closing balances are required', (string) $statement['diagnostic']);
    }

    public function test_matched_lines_do_not_affect_adjusted_balances(): void
    {
        $preparer = $this->createPreparer();
        $reconciliation = $this->createReconciliation($preparer);

        BankStatementLine::query()->create([
            'bank_reconciliation_id' => $reconciliation->id,
            'debit' => '100.00',
            'credit' => '0.00',
            'matched_status' => BankStatementLine::MATCH_MATCHED,
            'line_order' => 1,
            'is_ai_extracted' => false,
        ]);

        SapGlLine::query()->create([
            'bank_reconciliation_id' => $reconciliation->id,
            'debit' => '0.00',
            'credit' => '100.00',
            'matched_status' => SapGlLine::MATCH_MATCHED,
        ]);

        $statement = app(ReconciliationBalanceService::class)->reconciliationStatement($reconciliation);

        $this->assertTrue($statement['is_reconciled']);
        $this->assertSame(10000.0, $statement['adjusted_bank']);
        $this->assertSame(10000.0, $statement['adjusted_book']);
        $this->assertSame([], $statement['bank_items']);
        $this->assertSame([], $statement['book_items']);
    }
}
