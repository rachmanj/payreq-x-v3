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

class BankTransactionRecalculateBalanceTest extends TestCase
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

        $this->seedProjectPettyCashAccounts();
    }

    protected function seedProjectPettyCashAccounts(string $project = '021C', int $initialCashBalance = 10_000_000): void
    {
        Account::query()->create([
            'type' => 'cash',
            'account_number' => '11101005',
            'account_name' => 'Petty Cash',
            'project' => $project,
            'app_balance' => $initialCashBalance,
            'is_active' => true,
        ]);

        Account::query()->create([
            'type' => 'advance',
            'account_number' => '13101021',
            'account_name' => 'Advance Clearing',
            'project' => $project,
            'app_balance' => 100_000_000,
            'is_active' => true,
        ]);
    }

    protected function createAdminUser(): User
    {
        $user = User::factory()->create([
            'project' => '021C',
            'department_id' => $this->department->id,
        ]);
        $user->assignRole('admin');

        return $user;
    }

    protected function createCashierWithoutRecalculatePermission(): User
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

    protected function createPostedBankJournalWithUnbookedIncoming(int $amount = 5_000_000): array
    {
        $journal = VerificationJournal::query()->create([
            'nomor' => 'BT-RECALC-'.uniqid(),
            'date' => now()->toDateString(),
            'type' => 'bank',
            'project' => '021C',
            'bank_account' => '11201005',
            'description' => 'Unbooked incoming test',
            'amount' => $amount,
            'created_by' => User::factory()->create()->id,
            'status' => 'submitted',
            'validation_status' => VerificationJournal::VALIDATION_VALIDATED,
            'sap_journal_no' => 'SAP-UNBOOKED-001',
            'auto_validated_by_cashier' => true,
        ]);

        VerificationJournalDetail::query()->create([
            'verification_journal_id' => $journal->id,
            'realization_date' => now()->toDateString(),
            'account_code' => '11101005',
            'debit_credit' => 'debit',
            'description' => 'Test',
            'project' => '021C',
            'cost_center' => 'CC01',
            'amount' => $amount,
        ]);

        $incoming = Incoming::query()->create([
            'nomor' => $journal->nomor,
            'cashier_id' => $journal->created_by,
            'description' => 'Bank Transaction: '.$journal->nomor.' - '.$journal->description,
            'amount' => $amount,
            'project' => '021C',
            'receive_date' => now(),
            'will_post' => true,
            'sap_journal_no' => $journal->sap_journal_no,
        ]);

        return [$journal, $incoming];
    }

    public function test_recalculate_credits_petty_cash_and_creates_incoming_transaksi(): void
    {
        $admin = $this->createAdminUser();
        $amount = 5_000_000;
        [$journal, $incoming] = $this->createPostedBankJournalWithUnbookedIncoming($amount);
        $cashAccount = Account::query()->where('type', 'cash')->where('project', '021C')->orderBy('id')->firstOrFail();
        $advanceAccount = Account::query()->where('type', 'advance')->where('project', '021C')->orderBy('id')->firstOrFail();
        $advanceBefore = (int) $advanceAccount->app_balance;

        $this->actingAs($admin)
            ->post(route('cashier.bank-transactions.recalculate-balance', $journal->id))
            ->assertRedirect(route('cashier.bank-transactions.show', $journal->id))
            ->assertSessionHas('success');

        $cashAccount->refresh();
        $advanceAccount->refresh();

        $this->assertSame(10_000_000 + $amount, (int) $cashAccount->app_balance);
        $this->assertSame($advanceBefore - $amount, (int) $advanceAccount->app_balance);
        $this->assertTrue(
            Transaksi::query()
                ->where('document_type', 'incoming')
                ->where('document_id', $incoming->id)
                ->exists()
        );
    }

    public function test_second_recalculate_is_rejected_and_balance_unchanged(): void
    {
        $admin = $this->createAdminUser();
        [$journal, $incoming] = $this->createPostedBankJournalWithUnbookedIncoming();
        $route = route('cashier.bank-transactions.recalculate-balance', $journal->id);

        $this->actingAs($admin)->post($route)->assertSessionHas('success');

        $balanceAfterFirst = (int) Account::query()->where('type', 'cash')->where('project', '021C')->value('app_balance');

        $this->actingAs($admin)
            ->post($route)
            ->assertRedirect(route('cashier.bank-transactions.show', $journal->id))
            ->assertSessionHas('info');

        $this->assertSame($balanceAfterFirst, (int) Account::query()->where('type', 'cash')->where('project', '021C')->value('app_balance'));
        $this->assertSame(1, Transaksi::query()->where('document_type', 'incoming')->where('document_id', $incoming->id)->count());
    }

    public function test_user_without_permission_is_forbidden_and_balance_unchanged(): void
    {
        $cashier = $this->createCashierWithoutRecalculatePermission();
        [$journal] = $this->createPostedBankJournalWithUnbookedIncoming();
        $initialBalance = (int) Account::query()->where('type', 'cash')->where('project', '021C')->value('app_balance');

        $this->actingAs($cashier)
            ->from(route('cashier.bank-transactions.show', $journal->id))
            ->post(route('cashier.bank-transactions.recalculate-balance', $journal->id))
            ->assertRedirect(route('cashier.bank-transactions.show', $journal->id))
            ->assertSessionHas('alert_message');

        $this->assertSame($initialBalance, (int) Account::query()->where('type', 'cash')->where('project', '021C')->value('app_balance'));
    }

    public function test_show_hides_button_when_sap_not_posted_or_no_incoming(): void
    {
        $admin = $this->createAdminUser();

        $draftJournal = VerificationJournal::query()->create([
            'nomor' => 'BT-DRAFT',
            'date' => now()->toDateString(),
            'type' => 'bank',
            'project' => '021C',
            'bank_account' => '11201005',
            'description' => 'Draft',
            'amount' => 1_000_000,
            'created_by' => $admin->id,
            'status' => 'draft',
        ]);

        $this->actingAs($admin)
            ->get(route('cashier.bank-transactions.show', $draftJournal->id))
            ->assertOk()
            ->assertDontSee('Recalculate Balance');

        $noIncomingJournal = VerificationJournal::query()->create([
            'nomor' => 'BT-NO-INCOMING',
            'date' => now()->toDateString(),
            'type' => 'bank',
            'project' => '021C',
            'bank_account' => '11201005',
            'description' => 'SAP only',
            'amount' => 2_000_000,
            'created_by' => $admin->id,
            'status' => 'submitted',
            'sap_journal_no' => 'SAP-NO-INCOMING',
        ]);

        $this->actingAs($admin)
            ->get(route('cashier.bank-transactions.show', $noIncomingJournal->id))
            ->assertOk()
            ->assertDontSee('Recalculate Balance');
    }

    public function test_show_hides_button_when_incoming_already_booked_from_submit(): void
    {
        $user = $this->createCashierWithoutRecalculatePermission();
        $admin = $this->createAdminUser();

        Parameter::query()->updateOrCreate(
            ['name1' => 'cashier_vj_sap_limit', 'name2' => 'ALL'],
            ['param_value' => '100000000']
        );
        Parameter::query()->updateOrCreate(
            ['name1' => 'cashier_vj_sap_accounts', 'name2' => 'ALL'],
            ['param_value' => '11101005,11101008,11101010,11101004,11101006,71201001,71201006,71201007,71201002,71101001']
        );

        $journal = VerificationJournal::query()->create([
            'nomor' => 'BT-BOOKED-'.uniqid(),
            'date' => now()->toDateString(),
            'type' => 'bank',
            'project' => '021C',
            'bank_account' => '11201005',
            'description' => 'Booked via submit',
            'amount' => 150_000_000,
            'created_by' => $user->id,
            'status' => 'draft',
            'validation_status' => VerificationJournal::VALIDATION_PENDING,
        ]);

        VerificationJournalDetail::query()->create([
            'verification_journal_id' => $journal->id,
            'realization_date' => now()->toDateString(),
            'account_code' => '11101005',
            'debit_credit' => 'debit',
            'description' => 'Test',
            'project' => '021C',
            'cost_center' => 'CC01',
            'amount' => 150_000_000,
        ]);

        $this->mock(SapJournalSubmissionService::class, function ($mock) {
            $mock->shouldNotReceive('submit');
        });

        $this->actingAs($user)
            ->post(route('cashier.bank-transactions.submit', $journal->id))
            ->assertSessionHas('success');

        $this->actingAs($admin)
            ->get(route('cashier.bank-transactions.show', $journal->id))
            ->assertOk()
            ->assertDontSee('Recalculate Balance');
    }
}
