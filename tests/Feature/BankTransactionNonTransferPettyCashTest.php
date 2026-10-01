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
use Database\Seeders\RecalculateCashierBalancePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BankTransactionNonTransferPettyCashTest extends TestCase
{
    use RefreshDatabase;

    protected Department $department;

    protected function setUp(): void
    {
        parent::setUp();

        Role::query()->firstOrCreate(['name' => 'cashier'], ['guard_name' => 'web']);
        Role::query()->firstOrCreate(['name' => 'admin'], ['guard_name' => 'web']);
        $this->seed(RecalculateCashierBalancePermissionSeeder::class);

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
    protected function seedProjectPettyCashAccounts(string $project = '022C', int $initialCashBalance = 10_000_000): array
    {
        $cash = Account::query()->create([
            'type' => 'cash',
            'account_number' => '11101010',
            'account_name' => 'Petty Cash 022C',
            'project' => $project,
            'app_balance' => $initialCashBalance,
            'is_active' => true,
        ]);

        $advance = Account::query()->create([
            'type' => 'advance',
            'account_number' => '13101022',
            'account_name' => 'Advance Clearing',
            'project' => $project,
            'app_balance' => 100_000_000,
            'is_active' => true,
        ]);

        return ['cash' => $cash, 'advance' => $advance];
    }

    protected function createAuthorizedCashier(string $project = '022C'): User
    {
        $user = User::factory()->create([
            'project' => $project,
            'department_id' => $this->department->id,
        ]);
        $user->assignRole('cashier');
        Permission::firstOrCreate(['name' => 'cashier_submit_vj_to_sap', 'guard_name' => 'web']);
        $user->givePermissionTo('cashier_submit_vj_to_sap');

        return $user;
    }

    protected function createAdminUser(string $project = '022C'): User
    {
        $user = User::factory()->create([
            'project' => $project,
            'department_id' => $this->department->id,
        ]);
        $user->assignRole('admin');

        return $user;
    }

    protected function createBankAdminFeeJournal(User $creator, int $amount = 50_000): VerificationJournal
    {
        $journal = VerificationJournal::query()->create([
            'nomor' => 'BT-FEE-'.uniqid(),
            'date' => now()->toDateString(),
            'type' => 'bank',
            'project' => '022C',
            'bank_account' => '11201010',
            'description' => 'Biaya administrasi bank',
            'amount' => $amount,
            'created_by' => $creator->id,
            'status' => 'draft',
            'validation_status' => VerificationJournal::VALIDATION_PENDING,
            'sap_submission_attempts' => 0,
        ]);

        VerificationJournalDetail::query()->create([
            'verification_journal_id' => $journal->id,
            'realization_date' => $journal->date,
            'account_code' => '11201010',
            'debit_credit' => 'credit',
            'description' => 'Bank',
            'project' => '022C',
            'cost_center' => '30',
            'amount' => $amount,
        ]);

        VerificationJournalDetail::query()->create([
            'verification_journal_id' => $journal->id,
            'realization_date' => $journal->date,
            'account_code' => '71201001',
            'debit_credit' => 'debit',
            'description' => 'Biaya admin',
            'project' => '022C',
            'cost_center' => '30',
            'amount' => $amount,
        ]);

        return $journal;
    }

    protected function createTransferToPettyCashJournal(User $creator, int $amount = 5_000_000): VerificationJournal
    {
        $journal = VerificationJournal::query()->create([
            'nomor' => 'BT-PC-'.uniqid(),
            'date' => now()->toDateString(),
            'type' => 'bank',
            'project' => '022C',
            'bank_account' => '11201010',
            'description' => 'Tarik ke petty cash',
            'amount' => $amount,
            'created_by' => $creator->id,
            'status' => 'draft',
            'validation_status' => VerificationJournal::VALIDATION_PENDING,
            'sap_submission_attempts' => 0,
        ]);

        VerificationJournalDetail::query()->create([
            'verification_journal_id' => $journal->id,
            'realization_date' => $journal->date,
            'account_code' => '11201010',
            'debit_credit' => 'credit',
            'description' => 'Bank',
            'project' => '022C',
            'cost_center' => '30',
            'amount' => $amount,
        ]);

        VerificationJournalDetail::query()->create([
            'verification_journal_id' => $journal->id,
            'realization_date' => $journal->date,
            'account_code' => '11101010',
            'debit_credit' => 'debit',
            'description' => 'Kas',
            'project' => '022C',
            'cost_center' => '30',
            'amount' => $amount,
        ]);

        return $journal;
    }

    public function test_direct_sap_submit_bank_fee_does_not_create_incoming_or_change_balances(): void
    {
        $user = $this->createAuthorizedCashier();
        $journal = $this->createBankAdminFeeJournal($user);
        $cash = Account::query()->where('type', 'cash')->where('project', '022C')->firstOrFail();
        $advance = Account::query()->where('type', 'advance')->where('project', '022C')->firstOrFail();
        $cashBefore = (int) $cash->app_balance;
        $advanceBefore = (int) $advance->app_balance;

        $this->mock(SapJournalSubmissionService::class, function ($mock) {
            $mock->shouldReceive('submit')
                ->once()
                ->andReturnUsing(function (VerificationJournal $vj) {
                    $vj->update([
                        'status' => 'posted',
                        'sap_journal_no' => 'SAP-FEE-001',
                        'sap_submission_status' => 'success',
                    ]);

                    return [
                        'success' => true,
                        'sap_journal_no' => 'SAP-FEE-001',
                        'message' => 'OK',
                    ];
                });
        });

        $this->actingAs($user)
            ->post(route('cashier.bank-transactions.submit', $journal->id))
            ->assertRedirect(route('cashier.bank-transactions.show', $journal->id))
            ->assertSessionHas('success');

        $this->assertNull(Incoming::query()->where('nomor', $journal->nomor)->first());
        $cash->refresh();
        $advance->refresh();
        $this->assertSame($cashBefore, (int) $cash->app_balance);
        $this->assertSame($advanceBefore, (int) $advance->app_balance);
    }

    public function test_legacy_submit_bank_fee_does_not_create_incoming(): void
    {
        $user = $this->createAuthorizedCashier();
        $journal = $this->createBankAdminFeeJournal($user, 150_000_000);
        $cashBefore = (int) Account::query()->where('type', 'cash')->where('project', '022C')->value('app_balance');

        $this->mock(SapJournalSubmissionService::class, function ($mock) {
            $mock->shouldNotReceive('submit');
        });

        $this->actingAs($user)
            ->post(route('cashier.bank-transactions.submit', $journal->id))
            ->assertRedirect(route('cashier.bank-transactions.index'))
            ->assertSessionHas('success');

        $this->assertNull(Incoming::query()->where('nomor', $journal->nomor)->first());
        $this->assertSame($cashBefore, (int) Account::query()->where('type', 'cash')->where('project', '022C')->value('app_balance'));
    }

    public function test_transfer_to_petty_cash_still_creates_incoming_and_books_balance(): void
    {
        $user = $this->createAuthorizedCashier();
        $amount = 3_000_000;
        $journal = $this->createTransferToPettyCashJournal($user, $amount);
        $cash = Account::query()->where('type', 'cash')->where('project', '022C')->firstOrFail();

        $this->mock(SapJournalSubmissionService::class, function ($mock) {
            $mock->shouldReceive('submit')
                ->once()
                ->andReturnUsing(function (VerificationJournal $vj) {
                    $vj->update([
                        'status' => 'posted',
                        'sap_journal_no' => 'SAP-PC-001',
                        'sap_submission_status' => 'success',
                    ]);

                    return ['success' => true, 'sap_journal_no' => 'SAP-PC-001'];
                });
        });

        $this->actingAs($user)
            ->post(route('cashier.bank-transactions.submit', $journal->id))
            ->assertSessionHas('success');

        $incoming = Incoming::query()->where('nomor', $journal->nomor)->first();
        $this->assertNotNull($incoming);
        $cash->refresh();
        $this->assertSame(10_000_000 + $amount, (int) $cash->app_balance);
        $this->assertTrue(
            Transaksi::query()->where('document_type', 'incoming')->where('document_id', $incoming->id)->exists()
        );
    }

    public function test_recalculate_on_non_transfer_returns_not_transfer_without_booking(): void
    {
        $admin = $this->createAdminUser();
        $journal = $this->createBankAdminFeeJournal($admin, 25_000);
        $journal->update([
            'status' => 'posted',
            'sap_journal_no' => 'SAP-LEGACY-FEE',
            'validation_status' => VerificationJournal::VALIDATION_VALIDATED,
        ]);

        Incoming::query()->create([
            'nomor' => $journal->nomor,
            'cashier_id' => $admin->id,
            'description' => 'Bank Transaction: '.$journal->nomor.' - '.$journal->description,
            'amount' => $journal->amount,
            'project' => '022C',
            'receive_date' => now(),
            'will_post' => true,
            'sap_journal_no' => $journal->sap_journal_no,
        ]);

        $cashBefore = (int) Account::query()->where('type', 'cash')->where('project', '022C')->value('app_balance');

        $this->actingAs($admin)
            ->post(route('cashier.bank-transactions.recalculate-balance', $journal->id))
            ->assertRedirect(route('cashier.bank-transactions.show', $journal->id))
            ->assertSessionHas('info');

        $this->assertSame($cashBefore, (int) Account::query()->where('type', 'cash')->where('project', '022C')->value('app_balance'));
        $incoming = Incoming::query()->where('nomor', $journal->nomor)->firstOrFail();
        $this->assertFalse(
            Transaksi::query()->where('document_type', 'incoming')->where('document_id', $incoming->id)->exists()
        );
    }

    public function test_recalculate_on_transfer_still_books_balance(): void
    {
        $admin = $this->createAdminUser();
        $amount = 2_000_000;
        $journal = $this->createTransferToPettyCashJournal($admin, $amount);
        $journal->update([
            'status' => 'posted',
            'sap_journal_no' => 'SAP-PC-RECALC',
            'validation_status' => VerificationJournal::VALIDATION_VALIDATED,
        ]);

        $incoming = Incoming::query()->create([
            'nomor' => $journal->nomor,
            'cashier_id' => $admin->id,
            'description' => 'Bank Transaction: '.$journal->nomor.' - '.$journal->description,
            'amount' => $amount,
            'project' => '022C',
            'receive_date' => now(),
            'will_post' => true,
            'sap_journal_no' => $journal->sap_journal_no,
        ]);

        $this->actingAs($admin)
            ->post(route('cashier.bank-transactions.recalculate-balance', $journal->id))
            ->assertSessionHas('success');

        $this->assertTrue(
            Transaksi::query()->where('document_type', 'incoming')->where('document_id', $incoming->id)->exists()
        );
        $this->assertSame(10_000_000 + $amount, (int) Account::query()->where('type', 'cash')->where('project', '022C')->value('app_balance'));
    }

    public function test_manual_receive_rejects_incoming_from_non_transfer_bank_vj(): void
    {
        $user = $this->createAuthorizedCashier();
        $journal = $this->createBankAdminFeeJournal($user, 40_000);
        $journal->update(['status' => 'submitted']);

        $incoming = Incoming::query()->create([
            'nomor' => $journal->nomor,
            'cashier_id' => $user->id,
            'description' => 'Bank Transaction: '.$journal->nomor.' - '.$journal->description,
            'amount' => $journal->amount,
            'project' => '022C',
            'receive_date' => null,
            'will_post' => true,
        ]);

        $cashBefore = (int) Account::query()->where('type', 'cash')->where('project', '022C')->value('app_balance');

        $this->actingAs($user)
            ->from(route('cashier.incomings.index'))
            ->post(route('cashier.incomings.receive'), [
                'incoming_id' => $incoming->id,
                'receive_date' => now()->toDateString(),
            ])
            ->assertRedirect(route('cashier.incomings.index'))
            ->assertSessionHas('error');

        $this->assertSame($cashBefore, (int) Account::query()->where('type', 'cash')->where('project', '022C')->value('app_balance'));
        $this->assertFalse(
            Transaksi::query()->where('document_type', 'incoming')->where('document_id', $incoming->id)->exists()
        );
    }
}
