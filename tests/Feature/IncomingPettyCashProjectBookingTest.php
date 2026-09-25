<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Department;
use App\Models\Incoming;
use App\Models\Transaksi;
use App\Models\User;
use App\Models\VerificationJournal;
use App\Models\VerificationJournalDetail;
use Database\Seeders\RecalculateCashierBalancePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class IncomingPettyCashProjectBookingTest extends TestCase
{
    use RefreshDatabase;

    protected Department $department;

    protected function setUp(): void
    {
        parent::setUp();

        Role::query()->firstOrCreate(['name' => 'admin'], ['guard_name' => 'web']);
        Role::query()->firstOrCreate(['name' => 'cashier'], ['guard_name' => 'web']);
        $this->seed(RecalculateCashierBalancePermissionSeeder::class);

        $this->department = Department::query()->create([
            'department_name' => 'Finance Test',
            'sap_code' => '30',
        ]);

        $this->seedPettyCashPair('000H', 50_000_000, 200_000_000);
        $this->seedPettyCashPair('025C', 30_000_000, 150_000_000);
    }

    protected function seedPettyCashPair(string $project, int $cashBalance, int $advanceBalance): void
    {
        Account::query()->create([
            'type' => 'cash',
            'account_number' => '11101001-'.$project,
            'account_name' => 'Petty Cash '.$project,
            'project' => $project,
            'app_balance' => $cashBalance,
            'is_active' => true,
        ]);

        Account::query()->create([
            'type' => 'advance',
            'account_number' => '13101001-'.$project,
            'account_name' => 'Advance '.$project,
            'project' => $project,
            'app_balance' => $advanceBalance,
            'is_active' => true,
        ]);
    }

    protected function createHoAdmin(): User
    {
        $user = User::factory()->create([
            'project' => '000H',
            'department_id' => $this->department->id,
        ]);
        $user->assignRole('admin');

        return $user;
    }

    protected function createSiteCashier(): User
    {
        $user = User::factory()->create([
            'project' => '025C',
            'department_id' => $this->department->id,
        ]);
        $user->assignRole('cashier');

        return $user;
    }

    protected function createUnbooked025CBankIncoming(int $amount = 10_000_000): array
    {
        $journal = VerificationJournal::query()->create([
            'nomor' => 'VJ-025C-'.uniqid(),
            'date' => now()->toDateString(),
            'type' => 'bank',
            'project' => '025C',
            'bank_account' => '11201005',
            'description' => 'Site petty cash top-up',
            'amount' => $amount,
            'created_by' => User::factory()->create()->id,
            'status' => 'submitted',
            'validation_status' => VerificationJournal::VALIDATION_VALIDATED,
            'sap_journal_no' => 'SAP-025C-'.uniqid(),
            'auto_validated_by_cashier' => true,
        ]);

        VerificationJournalDetail::query()->create([
            'verification_journal_id' => $journal->id,
            'realization_date' => now()->toDateString(),
            'account_code' => '11101008',
            'debit_credit' => 'debit',
            'description' => 'Petty cash',
            'project' => '025C',
            'cost_center' => 'CC01',
            'amount' => $amount,
        ]);

        $incoming = Incoming::query()->create([
            'nomor' => $journal->nomor,
            'cashier_id' => $journal->created_by,
            'description' => 'Bank Transaction: '.$journal->nomor.' - '.$journal->description,
            'amount' => $amount,
            'project' => '025C',
            'receive_date' => now(),
            'will_post' => true,
            'sap_journal_no' => $journal->sap_journal_no,
        ]);

        return [$journal, $incoming];
    }

    public function test_ho_user_recalculate_credits_site_petty_cash_not_ho(): void
    {
        $hoAdmin = $this->createHoAdmin();
        $amount = 10_000_000;
        [$journal, $incoming] = $this->createUnbooked025CBankIncoming($amount);

        $hoCash = Account::query()->where('type', 'cash')->where('project', '000H')->orderBy('id')->firstOrFail();
        $siteCash = Account::query()->where('type', 'cash')->where('project', '025C')->orderBy('id')->firstOrFail();
        $hoCashBefore = (int) $hoCash->app_balance;
        $siteCashBefore = (int) $siteCash->app_balance;

        $this->actingAs($hoAdmin)
            ->post(route('cashier.bank-transactions.recalculate-balance', $journal->id))
            ->assertRedirect(route('cashier.bank-transactions.show', $journal->id))
            ->assertSessionHas('success');

        $hoCash->refresh();
        $siteCash->refresh();

        $this->assertSame($hoCashBefore, (int) $hoCash->app_balance);
        $this->assertSame($siteCashBefore + $amount, (int) $siteCash->app_balance);

        $transaksi = Transaksi::query()
            ->where('document_type', 'incoming')
            ->where('document_id', $incoming->id)
            ->firstOrFail();

        $this->assertSame((int) $siteCash->id, (int) $transaksi->account_id);
    }

    public function test_site_user_recalculate_credits_own_site_petty_cash(): void
    {
        $siteUser = $this->createSiteCashier();
        Permission::firstOrCreate(['name' => 'recalculate_cashier_balance', 'guard_name' => 'web']);
        $siteUser->givePermissionTo('recalculate_cashier_balance');
        $amount = 5_000_000;
        [$journal, $incoming] = $this->createUnbooked025CBankIncoming($amount);

        $siteCash = Account::query()->where('type', 'cash')->where('project', '025C')->orderBy('id')->firstOrFail();
        $siteCashBefore = (int) $siteCash->app_balance;

        $this->actingAs($siteUser)
            ->post(route('cashier.bank-transactions.recalculate-balance', $journal->id))
            ->assertSessionHas('success');

        $siteCash->refresh();
        $this->assertSame($siteCashBefore + $amount, (int) $siteCash->app_balance);

        $transaksi = Transaksi::query()
            ->where('document_type', 'incoming')
            ->where('document_id', $incoming->id)
            ->firstOrFail();

        $this->assertSame((int) $siteCash->id, (int) $transaksi->account_id);
    }

    public function test_site_user_receive_incoming_credits_site_account(): void
    {
        $siteUser = $this->createSiteCashier();
        $amount = 3_000_000;

        $incoming = Incoming::query()->create([
            'cashier_id' => $siteUser->id,
            'description' => 'Manual incoming test',
            'amount' => $amount,
            'project' => '025C',
        ]);

        $siteCash = Account::query()->where('type', 'cash')->where('project', '025C')->orderBy('id')->firstOrFail();
        $siteCashBefore = (int) $siteCash->app_balance;

        $this->actingAs($siteUser)
            ->post(route('cashier.incomings.receive'), [
                'incoming_id' => $incoming->id,
                'receive_date' => now()->toDateString(),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $siteCash->refresh();
        $this->assertSame($siteCashBefore + $amount, (int) $siteCash->app_balance);

        $transaksi = Transaksi::query()
            ->where('document_type', 'incoming')
            ->where('document_id', $incoming->id)
            ->firstOrFail();

        $this->assertSame((int) $siteCash->id, (int) $transaksi->account_id);
    }

    public function test_incoming_without_project_falls_back_to_logged_in_user_project(): void
    {
        $hoAdmin = $this->createHoAdmin();
        $amount = 2_000_000;

        $incoming = Incoming::query()->create([
            'cashier_id' => $hoAdmin->id,
            'description' => 'Legacy incoming without project',
            'amount' => $amount,
            'project' => null,
        ]);

        $hoCash = Account::query()->where('type', 'cash')->where('project', '000H')->orderBy('id')->firstOrFail();
        $siteCash = Account::query()->where('type', 'cash')->where('project', '025C')->orderBy('id')->firstOrFail();
        $hoCashBefore = (int) $hoCash->app_balance;
        $siteCashBefore = (int) $siteCash->app_balance;

        $this->actingAs($hoAdmin)
            ->post(route('cashier.incomings.receive'), [
                'incoming_id' => $incoming->id,
                'receive_date' => now()->toDateString(),
            ])
            ->assertSessionHas('success');

        $hoCash->refresh();
        $siteCash->refresh();

        $this->assertSame($hoCashBefore + $amount, (int) $hoCash->app_balance);
        $this->assertSame($siteCashBefore, (int) $siteCash->app_balance);
    }
}
