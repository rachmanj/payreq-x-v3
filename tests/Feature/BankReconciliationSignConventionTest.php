<?php

namespace Tests\Feature;

use App\Models\BankReconciliation;
use App\Models\BankStatementLine;
use App\Models\Giro;
use App\Models\ReconciliationMatchGroup;
use App\Models\SapGlLine;
use App\Models\User;
use App\Services\ReconciliationBalanceService;
use App\Services\ReconciliationMatchingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BankReconciliationSignConventionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::query()->firstOrCreate(['name' => 'cashier'], ['guard_name' => 'web']);
    }

    protected function createGiro(): Giro
    {
        $bankId = \Illuminate\Support\Facades\DB::table('banks')->insertGetId([
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

    protected function createPreparer(): User
    {
        $user = User::factory()->create(['project' => '000H']);
        $user->assignRole('cashier');

        return $user;
    }

    protected function createReconciliation(User $preparer): BankReconciliation
    {
        return BankReconciliation::query()->create([
            'giro_id' => $this->createGiro()->id,
            'dokumen_id' => null,
            'periode' => '2026-05-01',
            'source_mode' => BankReconciliation::SOURCE_MANUAL,
            'status' => BankReconciliation::STATUS_IN_REVIEW,
            'created_by' => $preparer->id,
        ]);
    }

    public function test_manual_match_succeeds_with_opposite_debit_credit_polarity(): void
    {
        $preparer = $this->createPreparer();
        $reconciliation = $this->createReconciliation($preparer);

        $bankLine = BankStatementLine::query()->create([
            'bank_reconciliation_id' => $reconciliation->id,
            'transaction_date' => '2026-05-04',
            'debit' => '48010000.00',
            'credit' => '0.00',
            'matched_status' => BankStatementLine::MATCH_UNMATCHED,
            'line_order' => 1,
            'is_ai_extracted' => false,
        ]);

        $sapLine = SapGlLine::query()->create([
            'bank_reconciliation_id' => $reconciliation->id,
            'posting_date' => '2026-05-04',
            'debit' => '0.00',
            'credit' => '48010000.00',
            'matched_status' => SapGlLine::MATCH_UNMATCHED,
        ]);

        $this->actingAs($preparer)->post(route('cashier.bank-reconciliation.match', $reconciliation), [
            'bank_statement_line_ids' => [$bankLine->id],
            'sap_gl_line_ids' => [$sapLine->id],
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('reconciliation_match_groups', [
            'bank_reconciliation_id' => $reconciliation->id,
            'match_type' => ReconciliationMatchGroup::TYPE_MANUAL,
            'difference' => '0.00',
        ]);

        $this->assertSame(BankStatementLine::MATCH_MANUAL, $bankLine->fresh()->matched_status);
        $this->assertSame(SapGlLine::MATCH_MANUAL, $sapLine->fresh()->matched_status);
    }

    public function test_manual_match_rejects_same_side_polarity(): void
    {
        $preparer = $this->createPreparer();
        $reconciliation = $this->createReconciliation($preparer);

        $bankLine = BankStatementLine::query()->create([
            'bank_reconciliation_id' => $reconciliation->id,
            'debit' => '100.00',
            'credit' => '0.00',
            'matched_status' => BankStatementLine::MATCH_UNMATCHED,
            'line_order' => 1,
            'is_ai_extracted' => false,
        ]);

        $sapLine = SapGlLine::query()->create([
            'bank_reconciliation_id' => $reconciliation->id,
            'debit' => '100.00',
            'credit' => '0.00',
            'matched_status' => SapGlLine::MATCH_UNMATCHED,
        ]);

        $this->actingAs($preparer)->post(route('cashier.bank-reconciliation.match', $reconciliation), [
            'bank_statement_line_ids' => [$bankLine->id],
            'sap_gl_line_ids' => [$sapLine->id],
        ])->assertRedirect()->assertSessionHasErrors('sap_gl_line_ids');

        $this->assertSame(BankStatementLine::MATCH_UNMATCHED, $bankLine->fresh()->matched_status);
        $this->assertSame(SapGlLine::MATCH_UNMATCHED, $sapLine->fresh()->matched_status);
    }

    public function test_balance_service_treats_opposite_polarity_as_balanced(): void
    {
        $preparer = $this->createPreparer();
        $reconciliation = $this->createReconciliation($preparer);

        BankStatementLine::query()->create([
            'bank_reconciliation_id' => $reconciliation->id,
            'debit' => '100.00',
            'credit' => '0.00',
            'matched_status' => BankStatementLine::MATCH_UNMATCHED,
            'line_order' => 1,
            'is_ai_extracted' => false,
        ]);

        SapGlLine::query()->create([
            'bank_reconciliation_id' => $reconciliation->id,
            'debit' => '0.00',
            'credit' => '100.00',
            'matched_status' => SapGlLine::MATCH_UNMATCHED,
        ]);

        $balance = app(ReconciliationBalanceService::class);

        $this->assertTrue($balance->isBalanced($reconciliation));
        $this->assertEquals(0.0, $balance->difference($reconciliation));
    }

    public function test_balance_service_treats_same_side_polarity_as_not_balanced(): void
    {
        $preparer = $this->createPreparer();
        $reconciliation = $this->createReconciliation($preparer);

        BankStatementLine::query()->create([
            'bank_reconciliation_id' => $reconciliation->id,
            'debit' => '100.00',
            'credit' => '0.00',
            'matched_status' => BankStatementLine::MATCH_UNMATCHED,
            'line_order' => 1,
            'is_ai_extracted' => false,
        ]);

        SapGlLine::query()->create([
            'bank_reconciliation_id' => $reconciliation->id,
            'debit' => '100.00',
            'credit' => '0.00',
            'matched_status' => SapGlLine::MATCH_UNMATCHED,
        ]);

        $balance = app(ReconciliationBalanceService::class);

        $this->assertFalse($balance->isBalanced($reconciliation));
        $this->assertEquals(200.0, $balance->difference($reconciliation));
    }

    public function test_auto_match_pairs_opposite_polarity_lines(): void
    {
        $preparer = $this->createPreparer();
        $reconciliation = $this->createReconciliation($preparer);

        BankStatementLine::query()->create([
            'bank_reconciliation_id' => $reconciliation->id,
            'transaction_date' => '2026-05-04',
            'debit' => '500.00',
            'credit' => '0.00',
            'matched_status' => BankStatementLine::MATCH_UNMATCHED,
            'line_order' => 1,
            'is_ai_extracted' => false,
        ]);

        SapGlLine::query()->create([
            'bank_reconciliation_id' => $reconciliation->id,
            'posting_date' => '2026-05-04',
            'debit' => '0.00',
            'credit' => '500.00',
            'matched_status' => SapGlLine::MATCH_UNMATCHED,
        ]);

        $matched = app(ReconciliationMatchingService::class)->autoMatch($reconciliation);

        $this->assertSame(1, $matched);
        $this->assertDatabaseHas('reconciliation_match_groups', [
            'bank_reconciliation_id' => $reconciliation->id,
            'match_type' => ReconciliationMatchGroup::TYPE_AUTO_EXACT,
            'difference' => '0.00',
        ]);
    }
}
