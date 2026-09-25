<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Department;
use App\Models\Incoming;
use App\Models\Parameter;
use App\Models\Transaksi;
use App\Models\User;
use App\Models\VerificationJournal;
use App\Models\VerificationJournalDetail;
use App\Services\SapJournalSubmissionService;
use Database\Seeders\CashierSubmitVjToSapPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BankTransactionDirectSapTest extends TestCase
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

        $this->seedCashierVjParameters();
        $this->seedProjectPettyCashAccounts();
    }

    protected function seedCashierVjParameters(): void
    {
        Parameter::query()->updateOrCreate(
            ['name1' => 'cashier_vj_sap_limit', 'name2' => 'ALL'],
            ['param_value' => '100000000']
        );

        Parameter::query()->updateOrCreate(
            ['name1' => 'cashier_vj_sap_accounts', 'name2' => 'ALL'],
            ['param_value' => '11101005,11101008,11101010,11101004,11101006,71201001,71201006,71201007,71201002,71101001']
        );
    }

    /**
     * @return array{cash: Account, advance: Account}
     */
    protected function seedProjectPettyCashAccounts(string $project = '021C', int $initialCashBalance = 10_000_000): array
    {
        $cash = Account::query()->create([
            'type' => 'cash',
            'account_number' => '11101005',
            'account_name' => 'Petty Cash',
            'project' => $project,
            'app_balance' => $initialCashBalance,
            'is_active' => true,
        ]);

        $advance = Account::query()->create([
            'type' => 'advance',
            'account_number' => '13101021',
            'account_name' => 'Advance Clearing',
            'project' => $project,
            'app_balance' => 100_000_000,
            'is_active' => true,
        ]);

        return ['cash' => $cash, 'advance' => $advance];
    }

    protected function assertIncomingBookedToPettyCash(Incoming $incoming, Account $cashAccount, int $expectedBalance): void
    {
        $this->assertTrue(
            Transaksi::query()
                ->where('document_type', 'incoming')
                ->where('document_id', $incoming->id)
                ->exists()
        );

        $cashAccount->refresh();
        $this->assertSame($expectedBalance, (int) $cashAccount->app_balance);
    }

    protected function createAuthorizedCashier(): User
    {
        $user = User::factory()->create([
            'project' => '021C',
            'department_id' => $this->department->id,
        ]);
        $user->assignRole('cashier');
        Permission::firstOrCreate(['name' => 'cashier_submit_vj_to_sap', 'guard_name' => 'web']);
        $user->givePermissionTo('cashier_submit_vj_to_sap');

        return $user;
    }

    protected function createBankJournal(User $creator, array $overrides = [], int $amount = 5_000_000): VerificationJournal
    {
        $journal = VerificationJournal::query()->create(array_merge([
            'nomor' => 'BT'.uniqid(),
            'date' => now()->toDateString(),
            'type' => 'bank',
            'project' => '021C',
            'bank_account' => '11201005',
            'description' => 'Test bank to PC',
            'amount' => $amount,
            'created_by' => $creator->id,
            'status' => 'draft',
            'validation_status' => VerificationJournal::VALIDATION_PENDING,
            'sap_submission_attempts' => 0,
        ], $overrides));

        VerificationJournalDetail::query()->create([
            'verification_journal_id' => $journal->id,
            'realization_date' => $journal->date,
            'account_code' => '11201005',
            'debit_credit' => 'credit',
            'description' => $journal->description,
            'project' => '021C',
            'cost_center' => '30',
            'amount' => $amount,
        ]);

        VerificationJournalDetail::query()->create([
            'verification_journal_id' => $journal->id,
            'realization_date' => $journal->date,
            'account_code' => '11101005',
            'debit_credit' => 'debit',
            'description' => 'PC top-up',
            'project' => '021C',
            'cost_center' => '30',
            'amount' => $amount,
        ]);

        return $journal;
    }

    public function test_authorized_cashier_direct_submit_posts_to_sap_and_creates_incoming(): void
    {
        $user = $this->createAuthorizedCashier();
        $journal = $this->createBankJournal($user);
        $amount = (int) $journal->amount;
        $cashAccount = Account::query()->where('type', 'cash')->where('project', '021C')->orderBy('id')->firstOrFail();

        $this->mock(SapJournalSubmissionService::class, function ($mock) {
            $mock->shouldReceive('submit')
                ->once()
                ->andReturnUsing(function (VerificationJournal $vj) {
                    $vj->update([
                        'status' => 'posted',
                        'sap_journal_no' => 'SAP-DIRECT-001',
                        'sap_submission_status' => 'success',
                    ]);

                    return [
                        'success' => true,
                        'sap_journal_no' => 'SAP-DIRECT-001',
                        'message' => 'Journal entry successfully submitted to SAP B1. Journal Number: SAP-DIRECT-001',
                    ];
                });
        });

        $this->actingAs($user)
            ->post(route('cashier.bank-transactions.submit', $journal->id))
            ->assertRedirect(route('cashier.bank-transactions.show', $journal->id))
            ->assertSessionHas('success');

        $journal->refresh();
        $this->assertSame(VerificationJournal::VALIDATION_VALIDATED, $journal->validation_status);
        $this->assertTrue($journal->auto_validated_by_cashier);
        $this->assertSame('posted', $journal->status);
        $this->assertSame('SAP-DIRECT-001', $journal->sap_journal_no);

        $incoming = Incoming::query()->where('nomor', $journal->nomor)->first();
        $this->assertNotNull($incoming);
        $this->assertSame('SAP-DIRECT-001', $incoming->sap_journal_no);
        $this->assertIncomingBookedToPettyCash($incoming, $cashAccount, 10_000_000 + $amount);
    }

    public function test_legacy_submit_credits_app_balance_and_creates_transaksi(): void
    {
        $user = $this->createAuthorizedCashier();
        $journal = $this->createBankJournal($user, [], 150_000_000);
        $amount = (int) $journal->amount;
        $cashAccount = Account::query()->where('type', 'cash')->where('project', '021C')->orderBy('id')->firstOrFail();

        $this->mock(SapJournalSubmissionService::class, function ($mock) {
            $mock->shouldNotReceive('submit');
        });

        $this->actingAs($user)
            ->post(route('cashier.bank-transactions.submit', $journal->id))
            ->assertRedirect(route('cashier.bank-transactions.index'))
            ->assertSessionHas('success');

        $incoming = Incoming::query()->where('nomor', $journal->nomor)->first();
        $this->assertNotNull($incoming);
        $this->assertIncomingBookedToPettyCash($incoming, $cashAccount, 10_000_000 + $amount);
    }

    public function test_manual_receive_after_bank_submit_is_rejected_by_anti_double_guard(): void
    {
        $user = $this->createAuthorizedCashier();
        $journal = $this->createBankJournal($user, [], 150_000_000);

        $this->mock(SapJournalSubmissionService::class, function ($mock) {
            $mock->shouldNotReceive('submit');
        });

        $this->actingAs($user)
            ->post(route('cashier.bank-transactions.submit', $journal->id))
            ->assertSessionHas('success');

        $incoming = Incoming::query()->where('nomor', $journal->nomor)->firstOrFail();
        $balanceAfterSubmit = (int) Account::query()->where('type', 'cash')->where('project', '021C')->value('app_balance');

        $this->actingAs($user)
            ->from(route('cashier.incomings.index'))
            ->post(route('cashier.incomings.receive'), [
                'incoming_id' => $incoming->id,
                'receive_date' => now()->toDateString(),
            ])
            ->assertRedirect(route('cashier.incomings.index'))
            ->assertSessionHas('error');

        $this->assertSame($balanceAfterSubmit, (int) Account::query()->where('type', 'cash')->where('project', '021C')->value('app_balance'));
        $this->assertSame(1, Transaksi::query()->where('document_type', 'incoming')->where('document_id', $incoming->id)->count());
    }

    public function test_sap_failure_leaves_no_incoming_and_allows_resubmit(): void
    {
        $user = $this->createAuthorizedCashier();
        $journal = $this->createBankJournal($user);
        $initialBalance = (int) Account::query()->where('type', 'cash')->where('project', '021C')->orderBy('id')->value('app_balance');

        $this->mock(SapJournalSubmissionService::class, function ($mock) {
            $mock->shouldReceive('submit')
                ->twice()
                ->andReturn([
                    'success' => false,
                    'message' => 'Failed to submit to SAP B1: Connection timeout',
                ]);
        });

        $this->actingAs($user)
            ->post(route('cashier.bank-transactions.submit', $journal->id))
            ->assertRedirect(route('cashier.bank-transactions.show', $journal->id))
            ->assertSessionHas('error');

        $journal->refresh();
        $this->assertSame('submitted', $journal->status);
        $this->assertNull($journal->sap_journal_no);
        $this->assertSame(0, Incoming::query()->where('nomor', $journal->nomor)->count());

        $this->assertSame($initialBalance, (int) Account::query()->where('type', 'cash')->where('project', '021C')->orderBy('id')->value('app_balance'));

        $this->actingAs($user)
            ->post(route('cashier.bank-transactions.submit', $journal->id))
            ->assertRedirect(route('cashier.bank-transactions.show', $journal->id))
            ->assertSessionHas('error');
    }

    public function test_amount_above_limit_uses_legacy_flow_without_sap(): void
    {
        $user = $this->createAuthorizedCashier();
        $journal = $this->createBankJournal($user, [], 150_000_000);

        $this->mock(SapJournalSubmissionService::class, function ($mock) {
            $mock->shouldNotReceive('submit');
        });

        $this->actingAs($user)
            ->post(route('cashier.bank-transactions.submit', $journal->id))
            ->assertRedirect(route('cashier.bank-transactions.index'))
            ->assertSessionHas('success');

        $journal->refresh();
        $this->assertSame(VerificationJournal::VALIDATION_PENDING, $journal->validation_status);
        $this->assertFalse($journal->auto_validated_by_cashier);
        $this->assertSame('submitted', $journal->status);
        $this->assertSame(1, Incoming::query()->where('nomor', $journal->nomor)->count());
    }

    public function test_store_rejects_debit_account_outside_transaction_type(): void
    {
        $user = $this->createAuthorizedCashier();

        $this->actingAs($user)
            ->post(route('cashier.bank-transactions.store'), [
                'date' => now()->toDateString(),
                'project' => '021C',
                'bank_account' => '11201005',
                'description' => 'Invalid account test',
                'transaction_type' => 'transfer_to_petty_cash',
                'account_code' => ['71201001'],
                'debit_credit' => ['debit'],
                'detail_description' => ['Should fail'],
                'project' => ['021C'],
                'cost_center' => ['30'],
                'amount' => [1000],
            ])
            ->assertSessionHasErrors('account_code');
    }

    public function test_user_without_permission_uses_legacy_submit_flow(): void
    {
        $user = User::factory()->create([
            'project' => '021C',
            'department_id' => $this->department->id,
        ]);
        $user->assignRole('cashier');

        $journal = $this->createBankJournal($user);

        $this->mock(SapJournalSubmissionService::class, function ($mock) {
            $mock->shouldNotReceive('submit');
        });

        $this->actingAs($user)
            ->post(route('cashier.bank-transactions.submit', $journal->id))
            ->assertRedirect(route('cashier.bank-transactions.index'));

        $journal->refresh();
        $this->assertSame(VerificationJournal::VALIDATION_PENDING, $journal->validation_status);
        $this->assertFalse($journal->auto_validated_by_cashier);
        $this->assertSame(1, Incoming::query()->where('nomor', $journal->nomor)->count());
    }

    public function test_parameters_control_eligibility(): void
    {
        Parameter::query()->where('name1', 'cashier_vj_sap_limit')->update(['param_value' => '1000']);

        $user = $this->createAuthorizedCashier();
        $journal = $this->createBankJournal($user, [], 5000);

        $this->mock(SapJournalSubmissionService::class, function ($mock) {
            $mock->shouldNotReceive('submit');
        });

        $this->actingAs($user)
            ->post(route('cashier.bank-transactions.submit', $journal->id))
            ->assertRedirect(route('cashier.bank-transactions.index'));

        $journal->refresh();
        $this->assertSame(VerificationJournal::VALIDATION_PENDING, $journal->validation_status);
    }

    public function test_create_edit_and_show_contain_transaction_type_and_sap_modal_text(): void
    {
        $user = $this->createAuthorizedCashier();
        $journal = $this->createBankJournal($user);

        $this->actingAs($user)
            ->get(route('cashier.bank-transactions.create'))
            ->assertOk()
            ->assertSee('Transaction Type', false)
            ->assertSee('Transfer to Petty Cash', false)
            ->assertSee('posted to SAP immediately', false);

        $this->actingAs($user)
            ->get(route('cashier.bank-transactions.edit', $journal->id))
            ->assertOk()
            ->assertSee('Bank Admin Fee', false);

        $this->actingAs($user)
            ->get(route('cashier.bank-transactions.show', $journal->id))
            ->assertOk()
            ->assertSee('posted immediately in SAP B1', false)
            ->assertSee('reversal (storno) by Accounting', false);
    }

    public function test_cashier_submit_vj_to_sap_permission_seeder(): void
    {
        foreach ([81, 130, 141] as $userId) {
            User::factory()->create(['id' => $userId, 'project' => '021C', 'department_id' => $this->department->id]);
        }

        $this->seed(CashierSubmitVjToSapPermissionSeeder::class);

        $permission = Permission::query()->where('name', 'cashier_submit_vj_to_sap')->first();
        $this->assertNotNull($permission);

        $cashierRole = Role::query()->where('name', 'cashier')->first();
        $this->assertTrue($cashierRole->hasPermissionTo('cashier_submit_vj_to_sap'));

        foreach ([81, 130, 141] as $userId) {
            $user = User::query()->find($userId);
            $this->assertTrue($user->hasPermissionTo('cashier_submit_vj_to_sap'));
        }
    }

    public function test_debit_account_outside_parameter_list_is_not_eligible_for_direct_sap(): void
    {
        $user = $this->createAuthorizedCashier();
        $journal = $this->createBankJournal($user);

        VerificationJournalDetail::query()->where('verification_journal_id', $journal->id)
            ->where('debit_credit', 'debit')
            ->update(['account_code' => '61202001']);

        $this->mock(SapJournalSubmissionService::class, function ($mock) {
            $mock->shouldNotReceive('submit');
        });

        $this->actingAs($user)
            ->post(route('cashier.bank-transactions.submit', $journal->id))
            ->assertRedirect(route('cashier.bank-transactions.index'));

        $this->assertSame(VerificationJournal::VALIDATION_PENDING, $journal->fresh()->validation_status);
    }

    public function test_show_displays_auto_validated_badge(): void
    {
        $user = $this->createAuthorizedCashier();
        $journal = $this->createBankJournal($user, [
            'status' => 'posted',
            'auto_validated_by_cashier' => true,
            'validation_status' => VerificationJournal::VALIDATION_VALIDATED,
            'sap_journal_no' => 'SAP-123',
        ]);

        $this->actingAs($user)
            ->get(route('cashier.bank-transactions.show', $journal->id))
            ->assertOk()
            ->assertSee('Auto-validated by cashier', false);
    }
}
