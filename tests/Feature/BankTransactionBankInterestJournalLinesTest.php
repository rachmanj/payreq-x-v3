<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\DocumentNumber;
use App\Models\Parameter;
use App\Models\User;
use App\Models\VerificationJournal;
use App\Models\VerificationJournalDetail;
use App\Services\CashierBankTransactionJournalLinesService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BankTransactionBankInterestJournalLinesTest extends TestCase
{
    use RefreshDatabase;

    protected Department $department;

    protected function setUp(): void
    {
        parent::setUp();

        Role::query()->firstOrCreate(['name' => 'cashier'], ['guard_name' => 'web']);

        $this->department = Department::query()->create([
            'department_name' => 'Finance Test',
            'sap_code' => '30',
        ]);

        Parameter::query()->updateOrCreate(
            ['name1' => 'cashier_vj_sap_limit', 'name2' => 'ALL'],
            ['param_value' => '100000000']
        );

        Parameter::query()->updateOrCreate(
            ['name1' => 'cashier_vj_sap_accounts', 'name2' => 'ALL'],
            ['param_value' => '11101005,71201001,71201006,71201007,71201002,71101001']
        );

        DocumentNumber::query()->create([
            'document_type' => 'verification-journal',
            'project' => '022C',
            'year' => (int) date('Y'),
            'last_number' => 0,
        ]);
    }

    protected function createCashier(): User
    {
        $user = User::factory()->create([
            'project' => '022C',
            'department_id' => $this->department->id,
        ]);
        $user->assignRole('cashier');
        Permission::firstOrCreate(['name' => 'cashier_submit_vj_to_sap', 'guard_name' => 'web']);

        return $user;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function basePayload(array $overrides = []): array
    {
        return array_merge([
            'date' => '2026-09-30',
            'bank_account' => '11201006',
            'description' => 'Bunga Bank',
            'transaction_type' => 'bank_interest',
            'account_code' => ['71101001'],
            'debit_credit' => ['debit'],
            'detail_description' => ['Pendapatan bunga'],
            'project' => ['022C'],
            'cost_center' => ['30'],
            'amount' => [3926.95],
        ], $overrides);
    }

    public function test_bank_interest_with_single_income_line_credits_income_and_debits_bank(): void
    {
        $user = $this->createCashier();

        $this->actingAs($user)
            ->post(route('cashier.bank-transactions.store'), $this->basePayload())
            ->assertRedirect(route('cashier.bank-transactions.index'))
            ->assertSessionHas('success');

        $journal = VerificationJournal::query()->latest('id')->first();
        $this->assertNotNull($journal);
        $this->assertSame(3926.95, (float) $journal->amount);

        $details = VerificationJournalDetail::query()
            ->where('verification_journal_id', $journal->id)
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $details);

        $bank = $details->firstWhere('account_code', '11201006');
        $income = $details->firstWhere('account_code', '71101001');

        $this->assertNotNull($bank);
        $this->assertSame('debit', $bank->debit_credit);
        $this->assertEqualsWithDelta(3926.95, (float) $bank->amount, 0.001);

        $this->assertNotNull($income);
        $this->assertSame('credit', $income->debit_credit);
        $this->assertEqualsWithDelta(3926.95, (float) $income->amount, 0.001);

        $this->assertJournalBalanced($details);
    }

    public function test_bank_interest_mixed_lines_match_expected_directions_and_net_bank(): void
    {
        $user = $this->createCashier();

        $payload = $this->basePayload([
            'account_code' => ['71201001', '71101001', '71201007'],
            'debit_credit' => ['debit', 'debit', 'debit'],
            'detail_description' => ['Admin', 'Giro', 'Pajak bunga'],
            'project' => ['022C', '022C', '022C'],
            'cost_center' => ['30', '30', '30'],
            'amount' => [25000, 6374.31, 1274.86],
        ]);

        $this->actingAs($user)
            ->post(route('cashier.bank-transactions.store'), $payload)
            ->assertRedirect(route('cashier.bank-transactions.index'));

        $journal = VerificationJournal::query()->latest('id')->first();
        $this->assertEqualsWithDelta(26274.86, (float) $journal->amount, 0.001);

        $byAccount = VerificationJournalDetail::query()
            ->where('verification_journal_id', $journal->id)
            ->get()
            ->keyBy('account_code');

        $this->assertSame('debit', $byAccount->get('71201001')->debit_credit);
        $this->assertEqualsWithDelta(25000, (float) $byAccount->get('71201001')->amount, 0.001);

        $this->assertSame('debit', $byAccount->get('71201007')->debit_credit);
        $this->assertEqualsWithDelta(1274.86, (float) $byAccount->get('71201007')->amount, 0.001);

        $this->assertSame('credit', $byAccount->get('71101001')->debit_credit);
        $this->assertEqualsWithDelta(6374.31, (float) $byAccount->get('71101001')->amount, 0.001);

        $this->assertSame('credit', $byAccount->get('11201006')->debit_credit);
        $this->assertEqualsWithDelta(19900.55, (float) $byAccount->get('11201006')->amount, 0.001);

        $this->assertJournalBalanced($byAccount->values());
    }

    public function test_bank_interest_without_income_account_is_rejected(): void
    {
        $user = $this->createCashier();

        $payload = $this->basePayload([
            'account_code' => ['71201001'],
            'amount' => [1000],
            'detail_description' => ['Biaya saja'],
        ]);

        $this->actingAs($user)
            ->post(route('cashier.bank-transactions.store'), $payload)
            ->assertSessionHasErrors('account_code');

        $this->assertSame(0, VerificationJournal::query()->count());
    }

    public function test_transfer_to_petty_cash_keeps_bank_credit_and_cashier_debit(): void
    {
        $user = $this->createCashier();

        DocumentNumber::query()->create([
            'document_type' => 'verification-journal',
            'project' => '021C',
            'year' => (int) date('Y'),
            'last_number' => 0,
        ]);

        $payload = [
            'date' => now()->toDateString(),
            'project' => '021C',
            'bank_account' => '11201005',
            'description' => 'PC top-up',
            'transaction_type' => 'transfer_to_petty_cash',
            'account_code' => ['11101005'],
            'debit_credit' => ['debit'],
            'detail_description' => ['Petty cash'],
            'project' => ['021C'],
            'cost_center' => ['30'],
            'amount' => [5_000_000],
        ];

        $this->actingAs($user)
            ->post(route('cashier.bank-transactions.store'), $payload)
            ->assertRedirect(route('cashier.bank-transactions.index'));

        $journal = VerificationJournal::query()->latest('id')->first();
        $details = VerificationJournalDetail::query()->where('verification_journal_id', $journal->id)->get();

        $this->assertEqualsWithDelta(5_000_000, (float) $journal->amount, 0.001);
        $this->assertSame('credit', $details->firstWhere('account_code', '11201005')->debit_credit);
        $this->assertSame('debit', $details->firstWhere('account_code', '11101005')->debit_credit);
    }

    public function test_bank_admin_fee_keeps_bank_credit_and_fee_debit(): void
    {
        $user = $this->createCashier();

        $payload = [
            'date' => now()->toDateString(),
            'project' => '022C',
            'bank_account' => '11201006',
            'description' => 'Biaya admin',
            'transaction_type' => 'bank_admin_fee',
            'account_code' => ['71201001'],
            'debit_credit' => ['debit'],
            'detail_description' => ['Admin bank'],
            'project' => ['022C'],
            'cost_center' => ['30'],
            'amount' => [50_000],
        ];

        $this->actingAs($user)
            ->post(route('cashier.bank-transactions.store'), $payload)
            ->assertRedirect(route('cashier.bank-transactions.index'));

        $journal = VerificationJournal::query()->latest('id')->first();
        $details = VerificationJournalDetail::query()->where('verification_journal_id', $journal->id)->get();

        $this->assertEqualsWithDelta(50_000, (float) $journal->amount, 0.001);
        $this->assertSame('credit', $details->firstWhere('account_code', '11201006')->debit_credit);
        $this->assertSame('debit', $details->firstWhere('account_code', '71201001')->debit_credit);
    }

    public function test_update_applies_same_bank_interest_rules_as_store(): void
    {
        $user = $this->createCashier();

        $journal = VerificationJournal::query()->create([
            'nomor' => 'BT-INTEREST-EDIT',
            'date' => '2026-04-02',
            'type' => 'bank',
            'project' => '022C',
            'bank_account' => '11201006',
            'description' => 'Bunga Bank',
            'amount' => 1000,
            'created_by' => $user->id,
            'status' => 'draft',
        ]);

        VerificationJournalDetail::query()->create([
            'verification_journal_id' => $journal->id,
            'realization_date' => $journal->date,
            'account_code' => '11201006',
            'debit_credit' => 'credit',
            'description' => 'old',
            'project' => '022C',
            'cost_center' => '30',
            'amount' => 1000,
        ]);

        $payload = $this->basePayload([
            'amount' => [3926.95],
        ]);

        $this->actingAs($user)
            ->put(route('cashier.bank-transactions.update', $journal->id), $payload)
            ->assertRedirect(route('cashier.bank-transactions.index'));

        $journal->refresh();
        $this->assertEqualsWithDelta(3926.95, (float) $journal->amount, 0.001);

        $details = VerificationJournalDetail::query()
            ->where('verification_journal_id', $journal->id)
            ->get();

        $this->assertSame('debit', $details->firstWhere('account_code', '11201006')->debit_credit);
        $this->assertSame('credit', $details->firstWhere('account_code', '71101001')->debit_credit);
        $this->assertJournalBalanced($details);
    }

    public function test_update_mixed_bank_interest_header_matches_store(): void
    {
        $user = $this->createCashier();

        $journal = VerificationJournal::query()->create([
            'nomor' => 'BT-INTEREST-MIXED-EDIT',
            'date' => '2026-04-02',
            'type' => 'bank',
            'project' => '022C',
            'bank_account' => '11201006',
            'description' => 'Bunga Bank',
            'amount' => 32_649.17,
            'created_by' => $user->id,
            'status' => 'draft',
        ]);

        VerificationJournalDetail::query()->create([
            'verification_journal_id' => $journal->id,
            'realization_date' => $journal->date,
            'account_code' => '11201006',
            'debit_credit' => 'credit',
            'description' => 'old',
            'project' => '022C',
            'cost_center' => '30',
            'amount' => 1000,
        ]);

        $payload = $this->basePayload([
            'account_code' => ['71201001', '71101001', '71201007'],
            'debit_credit' => ['debit', 'debit', 'debit'],
            'detail_description' => ['Admin', 'Giro', 'Pajak bunga'],
            'project' => ['022C', '022C', '022C'],
            'cost_center' => ['30', '30', '30'],
            'amount' => [25000, 6374.31, 1274.86],
        ]);

        $this->actingAs($user)
            ->put(route('cashier.bank-transactions.update', $journal->id), $payload)
            ->assertRedirect(route('cashier.bank-transactions.index'));

        $journal->refresh();
        $this->assertEqualsWithDelta(26274.86, (float) $journal->amount, 0.001);
        $this->assertJournalBalanced(
            VerificationJournalDetail::query()->where('verification_journal_id', $journal->id)->get()
        );
    }

    public function test_journal_lines_service_builds_balanced_mixed_interest_rows(): void
    {
        $service = app(CashierBankTransactionJournalLinesService::class);

        $rows = $service->buildVerificationJournalDetailRows(
            'bank_interest',
            '11201006',
            'Bunga Bank',
            '022C',
            '30',
            ['71201001', '71101001', '71201007'],
            ['a', 'b', 'c'],
            ['022C', '022C', '022C'],
            ['30', '30', '30'],
            [25000, 6374.31, 1274.86],
        );

        $debit = 0.0;
        $credit = 0.0;
        foreach ($rows as $row) {
            if ($row['debit_credit'] === 'debit') {
                $debit += (float) $row['amount'];
            } else {
                $credit += (float) $row['amount'];
            }
        }

        $this->assertEqualsWithDelta($debit, $credit, 0.001);
    }

    /**
     * @param  iterable<VerificationJournalDetail>  $details
     */
    protected function assertJournalBalanced(iterable $details): void
    {
        $debit = 0.0;
        $credit = 0.0;

        foreach ($details as $detail) {
            if ($detail->debit_credit === 'debit') {
                $debit += (float) $detail->amount;
            } else {
                $credit += (float) $detail->amount;
            }
        }

        $this->assertEqualsWithDelta($debit, $credit, 0.001);
    }
}
