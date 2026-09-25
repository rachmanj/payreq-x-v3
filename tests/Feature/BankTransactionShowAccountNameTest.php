<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Department;
use App\Models\User;
use App\Models\VerificationJournal;
use App\Models\VerificationJournalDetail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BankTransactionShowAccountNameTest extends TestCase
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
    }

    protected function createCashier(): User
    {
        $user = User::factory()->create([
            'project' => '021C',
            'department_id' => $this->department->id,
        ]);
        $user->assignRole('cashier');

        return $user;
    }

    protected function createBankJournalWithDetails(User $creator, array $detailAccountCodes): VerificationJournal
    {
        $journal = VerificationJournal::query()->create([
            'nomor' => 'BT-ACCT-'.uniqid(),
            'date' => now()->toDateString(),
            'type' => 'bank',
            'project' => '021C',
            'bank_account' => '11201005',
            'description' => 'Account name column test',
            'amount' => 1_000_000,
            'created_by' => $creator->id,
            'status' => 'draft',
        ]);

        foreach ($detailAccountCodes as $index => $accountCode) {
            VerificationJournalDetail::query()->create([
                'verification_journal_id' => $journal->id,
                'realization_date' => $journal->date,
                'account_code' => $accountCode,
                'debit_credit' => $index === 0 ? 'credit' : 'debit',
                'description' => 'Line '.$index,
                'project' => '021C',
                'cost_center' => '30',
                'amount' => 1_000_000,
            ]);
        }

        return $journal;
    }

    public function test_show_displays_account_name_column_header(): void
    {
        $user = $this->createCashier();
        $journal = $this->createBankJournalWithDetails($user, ['11201005', '11101005']);

        $this->actingAs($user)
            ->get(route('cashier.bank-transactions.show', $journal->id))
            ->assertOk()
            ->assertSee('Account Name', false);
    }

    public function test_show_displays_master_account_name_for_known_account(): void
    {
        $user = $this->createCashier();

        Account::query()->create([
            'type' => 'cash',
            'account_number' => '11101005',
            'account_name' => 'Petty Cash',
            'project' => '021C',
            'app_balance' => 10_000_000,
            'is_active' => true,
        ]);

        $journal = $this->createBankJournalWithDetails($user, ['11201005', '11101005']);

        $this->actingAs($user)
            ->get(route('cashier.bank-transactions.show', $journal->id))
            ->assertOk()
            ->assertSee('Petty Cash', false);
    }

    public function test_show_falls_back_to_account_code_when_not_in_master(): void
    {
        $user = $this->createCashier();
        $unknownAccount = '99999999';
        $journal = $this->createBankJournalWithDetails($user, ['11201005', $unknownAccount]);

        $response = $this->actingAs($user)
            ->get(route('cashier.bank-transactions.show', $journal->id));

        $response->assertOk();
        $this->assertStringContainsString($unknownAccount, $response->getContent());
    }
}
