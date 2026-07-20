<?php

namespace Tests\Feature;

use App\Jobs\FetchSapGlLinesJob;
use App\Jobs\ParseBankStatementJob;
use App\Models\BankReconciliation;
use App\Models\BankStatementLine;
use App\Models\Dokumen;
use App\Models\Giro;
use App\Models\SapGlLine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BankReconciliationManualValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'validate_bank_reconciliation'], ['guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'akses_koran'], ['guard_name' => 'web']);
        Role::query()->firstOrCreate(['name' => 'cashier'], ['guard_name' => 'web']);
        Role::query()->firstOrCreate(['name' => 'admin'], ['guard_name' => 'web']);
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
        $user->givePermissionTo('akses_koran');

        return $user;
    }

    protected function createValidator(): User
    {
        $user = User::factory()->create(['project' => '000H']);
        $user->assignRole('admin');
        $user->givePermissionTo(['validate_bank_reconciliation', 'akses_koran']);

        return $user;
    }

    protected function createReconciliation(User $preparer, string $sourceMode = BankReconciliation::SOURCE_MANUAL): BankReconciliation
    {
        $giro = $this->createGiro();

        return BankReconciliation::query()->create([
            'giro_id' => $giro->id,
            'dokumen_id' => null,
            'periode' => '2026-02-01',
            'source_mode' => $sourceMode,
            'status' => BankReconciliation::STATUS_IN_REVIEW,
            'created_by' => $preparer->id,
        ]);
    }

    public function test_manual_store_skips_pdf_parse_job(): void
    {
        Queue::fake();

        $user = $this->createPreparer();
        $giro = $this->createGiro();

        $this->actingAs($user)->post(route('cashier.bank-reconciliation.store'), [
            'giro_id' => $giro->id,
            'source_mode' => BankReconciliation::SOURCE_MANUAL,
            'periode' => '2026-02',
        ])->assertRedirect();

        Queue::assertNotPushed(ParseBankStatementJob::class);
        Queue::assertPushed(FetchSapGlLinesJob::class);

        $this->assertDatabaseHas('bank_reconciliations', [
            'giro_id' => $giro->id,
            'source_mode' => BankReconciliation::SOURCE_MANUAL,
            'status' => BankReconciliation::STATUS_IN_REVIEW,
        ]);
    }

    public function test_ai_store_dispatches_parse_job(): void
    {
        Queue::fake();

        $user = $this->createPreparer();
        $giro = $this->createGiro();
        $dokumen = Dokumen::query()->create([
            'filename1' => 'koran_test.pdf',
            'giro_id' => $giro->id,
            'type' => 'koran',
            'project' => '000H',
            'periode' => '2026-02-01',
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)->post(route('cashier.bank-reconciliation.store'), [
            'giro_id' => $giro->id,
            'source_mode' => BankReconciliation::SOURCE_AI,
            'dokumen_id' => $dokumen->id,
            'periode' => '2026-02',
        ])->assertRedirect();

        Queue::assertPushed(ParseBankStatementJob::class);
        Queue::assertPushed(FetchSapGlLinesJob::class);
    }

    public function test_can_add_update_and_delete_unmatched_bank_line(): void
    {
        $preparer = $this->createPreparer();
        $reconciliation = $this->createReconciliation($preparer);

        $this->actingAs($preparer)->post(route('cashier.bank-reconciliation.lines.store', $reconciliation), [
            'transaction_date' => '2026-02-10',
            'description' => 'Transfer in',
            'debit' => 1000,
            'credit' => 0,
        ])->assertRedirect()->assertSessionHas('success');

        $line = BankStatementLine::query()->first();
        $this->assertFalse((bool) $line->is_ai_extracted);

        $this->actingAs($preparer)->put(route('cashier.bank-reconciliation.lines.update', [$reconciliation, $line]), [
            'transaction_date' => '2026-02-11',
            'description' => 'Updated transfer',
            'debit' => 1500,
            'credit' => 0,
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertSame('1500.00', $line->fresh()->debit);

        $this->actingAs($preparer)->delete(route('cashier.bank-reconciliation.lines.destroy', [$reconciliation, $line]))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('bank_statement_lines', ['id' => $line->id]);
    }

    public function test_excluding_one_sided_line_drives_difference_to_zero(): void
    {
        $preparer = $this->createPreparer();
        $reconciliation = $this->createReconciliation($preparer);

        BankStatementLine::query()->create([
            'bank_reconciliation_id' => $reconciliation->id,
            'debit' => '500.00',
            'credit' => '0.00',
            'matched_status' => BankStatementLine::MATCH_UNMATCHED,
            'line_order' => 1,
            'is_ai_extracted' => false,
        ]);

        SapGlLine::query()->create([
            'bank_reconciliation_id' => $reconciliation->id,
            'debit' => '0.00',
            'credit' => '0.00',
            'matched_status' => SapGlLine::MATCH_UNMATCHED,
        ]);

        $balance = app(\App\Services\ReconciliationBalanceService::class);
        $this->assertFalse($balance->isBalanced($reconciliation));

        $bankLine = BankStatementLine::query()->first();

        $this->actingAs($preparer)->post(route('cashier.bank-reconciliation.lines.exclude', [$reconciliation, $bankLine]), [
            'exclude_reason' => 'Bank-only fee, no GL entry',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertTrue($balance->isBalanced($reconciliation->fresh()));
    }

    public function test_submit_blocked_when_difference_not_zero(): void
    {
        $preparer = $this->createPreparer();
        $reconciliation = $this->createReconciliation($preparer);
        $reconciliation->update([
            'opening_balance_bank' => '1000.00',
            'closing_balance_bank' => '1100.00',
            'opening_balance_book' => '1000.00',
            'closing_balance_book' => '1000.00',
        ]);

        BankStatementLine::query()->create([
            'bank_reconciliation_id' => $reconciliation->id,
            'debit' => '100.00',
            'credit' => '0.00',
            'matched_status' => BankStatementLine::MATCH_UNMATCHED,
            'line_order' => 1,
            'is_ai_extracted' => false,
        ]);

        $this->actingAs($preparer)->post(route('cashier.bank-reconciliation.submit', $reconciliation))
            ->assertRedirect()
            ->assertSessionHasErrors('balance');

        $this->assertNull($reconciliation->fresh()->validation_status);
    }

    public function test_submit_succeeds_when_balanced(): void
    {
        $preparer = $this->createPreparer();
        $reconciliation = $this->createReconciliation($preparer);
        $reconciliation->update([
            'opening_balance_bank' => '1000.00',
            'closing_balance_bank' => '1000.00',
            'opening_balance_book' => '1000.00',
            'closing_balance_book' => '1000.00',
        ]);

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

        $this->actingAs($preparer)->post(route('cashier.bank-reconciliation.submit', $reconciliation))
            ->assertRedirect()
            ->assertSessionHas('success');

        $reconciliation->refresh();
        $this->assertSame(BankReconciliation::VALIDATION_PENDING, $reconciliation->validation_status);
        $this->assertSame($preparer->id, $reconciliation->submitted_by);
    }

    public function test_submit_blocked_when_closing_balances_missing(): void
    {
        $preparer = $this->createPreparer();
        $reconciliation = $this->createReconciliation($preparer);

        $this->actingAs($preparer)->post(route('cashier.bank-reconciliation.submit', $reconciliation))
            ->assertRedirect()
            ->assertSessionHasErrors('balance');

        $this->assertNull($reconciliation->fresh()->validation_status);
    }

    public function test_can_update_opening_and_closing_balances(): void
    {
        $preparer = $this->createPreparer();
        $reconciliation = $this->createReconciliation($preparer);

        $this->actingAs($preparer)->put(route('cashier.bank-reconciliation.balances.update', $reconciliation), [
            'opening_balance_bank' => 1000,
            'closing_balance_bank' => 1200,
            'opening_balance_book' => 1000,
            'closing_balance_book' => 1150,
        ])->assertRedirect()->assertSessionHas('success');

        $reconciliation->refresh();
        $this->assertSame('1000.00', (string) $reconciliation->opening_balance_bank);
        $this->assertSame('1200.00', (string) $reconciliation->closing_balance_bank);
        $this->assertSame('1000.00', (string) $reconciliation->opening_balance_book);
        $this->assertSame('1150.00', (string) $reconciliation->closing_balance_book);
    }

    public function test_can_classify_unmatched_bank_line(): void
    {
        $preparer = $this->createPreparer();
        $reconciliation = $this->createReconciliation($preparer);

        $line = BankStatementLine::query()->create([
            'bank_reconciliation_id' => $reconciliation->id,
            'debit' => '25.00',
            'credit' => '0.00',
            'matched_status' => BankStatementLine::MATCH_UNMATCHED,
            'line_order' => 1,
            'is_ai_extracted' => false,
        ]);

        $this->actingAs($preparer)->post(route('cashier.bank-reconciliation.lines.classify', [$reconciliation, $line]), [
            'reconciling_type' => BankStatementLine::TYPE_CHARGE_NOT_BOOKED,
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertSame(BankStatementLine::TYPE_CHARGE_NOT_BOOKED, $line->fresh()->reconciling_type);
    }

    public function test_preparer_cannot_validate_own_reconciliation(): void
    {
        $preparer = $this->createPreparer();
        $preparer->givePermissionTo('validate_bank_reconciliation');
        $reconciliation = $this->createReconciliation($preparer);
        $reconciliation->update([
            'validation_status' => BankReconciliation::VALIDATION_PENDING,
            'submitted_by' => $preparer->id,
            'submitted_at' => now(),
        ]);

        $this->actingAs($preparer)->post(route('cashier.bank-reconciliation.validate', $reconciliation))
            ->assertForbidden();
    }

    public function test_admin_validator_can_validate_submitted_reconciliation(): void
    {
        $preparer = $this->createPreparer();
        $validator = $this->createValidator();
        $reconciliation = $this->createReconciliation($preparer);
        $reconciliation->update([
            'validation_status' => BankReconciliation::VALIDATION_PENDING,
            'submitted_by' => $preparer->id,
            'submitted_at' => now(),
        ]);

        $this->actingAs($validator)->post(route('cashier.bank-reconciliation.validate', $reconciliation))
            ->assertRedirect(route('cashier.bank-reconciliation.report', $reconciliation))
            ->assertSessionHas('success');

        $reconciliation->refresh();
        $this->assertSame(BankReconciliation::VALIDATION_VALIDATED, $reconciliation->validation_status);
        $this->assertSame(BankReconciliation::STATUS_COMPLETED, $reconciliation->status);
        $this->assertSame($validator->id, $reconciliation->validated_by);
    }

    public function test_validated_report_page_includes_floating_print_button(): void
    {
        $preparer = $this->createPreparer();
        $reconciliation = $this->createReconciliation($preparer);
        $reconciliation->update([
            'validation_status' => BankReconciliation::VALIDATION_VALIDATED,
            'status' => BankReconciliation::STATUS_COMPLETED,
            'validated_at' => now(),
        ]);

        $this->actingAs($preparer)
            ->get(route('cashier.bank-reconciliation.report', $reconciliation))
            ->assertOk()
            ->assertSee('floating-buttons-br', false)
            ->assertSee('onclick="window.print()"', false)
            ->assertSee('VALIDATED', false);
    }

    public function test_validator_can_reject_and_reopen_reconciliation(): void
    {
        $preparer = $this->createPreparer();
        $validator = $this->createValidator();
        $reconciliation = $this->createReconciliation($preparer);
        $reconciliation->update([
            'validation_status' => BankReconciliation::VALIDATION_PENDING,
            'submitted_by' => $preparer->id,
            'submitted_at' => now(),
        ]);

        $this->actingAs($validator)->post(route('cashier.bank-reconciliation.reject', $reconciliation), [
            'rejection_reason' => 'Outstanding items not explained',
        ])->assertRedirect()->assertSessionHas('success');

        $reconciliation->refresh();
        $this->assertSame(BankReconciliation::VALIDATION_REJECTED, $reconciliation->validation_status);
        $this->assertSame(BankReconciliation::STATUS_IN_REVIEW, $reconciliation->status);
        $this->assertSame('Outstanding items not explained', $reconciliation->rejection_reason);
        $this->assertFalse($reconciliation->isLockedForEditing());
    }
}
