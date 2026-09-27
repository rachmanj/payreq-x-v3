<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Bilyet;
use App\Models\Department;
use App\Models\DocumentNumber;
use App\Models\Giro;
use App\Models\Incoming;
use App\Models\Parameter;
use App\Models\User;
use App\Models\VerificationJournal;
use App\Models\VerificationJournalDetail;
use App\Services\CashierBankTransactionDirectSapService;
use App\Services\SapJournalEntryBuilder;
use App\Services\SapJournalSubmissionService;
use Database\Seeders\CashierSubmitVjToSapPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BankTransactionChequeBilyetTest extends TestCase
{
    use RefreshDatabase;

    protected Department $department;

    protected Giro $matchingGiro;

    protected Giro $otherGiro;

    protected Bilyet $matchingBilyet;

    protected Bilyet $otherBilyet;

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
        $this->seedVerificationJournalDocumentNumber();

        $bankId = DB::table('banks')->insertGetId([
            'name' => 'Mandiri',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->matchingGiro = Giro::query()->create([
            'acc_no' => '149-0019306770',
            'acc_name' => 'Bank Mandiri',
            'bank_id' => $bankId,
            'type' => 'giro',
            'project' => '021C',
            'sap_account' => '11201005',
        ]);

        $this->otherGiro = Giro::query()->create([
            'acc_no' => '149-other',
            'acc_name' => 'Other Bank',
            'bank_id' => $bankId,
            'type' => 'giro',
            'project' => '021C',
            'sap_account' => '11209999',
        ]);

        $this->matchingBilyet = Bilyet::query()->create([
            'giro_id' => $this->matchingGiro->id,
            'prefix' => 'JM',
            'nomor' => '130552',
            'type' => 'cek',
            'bilyet_date' => '2024-06-01',
            'amount' => 5_000_000,
            'status' => 'onhand',
            'project' => '021C',
        ]);

        $this->otherBilyet = Bilyet::query()->create([
            'giro_id' => $this->otherGiro->id,
            'prefix' => 'XX',
            'nomor' => '999',
            'type' => 'cek',
            'bilyet_date' => '2024-06-02',
            'amount' => 1_000_000,
            'status' => 'release',
            'project' => '021C',
        ]);
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

    protected function seedProjectPettyCashAccounts(): void
    {
        Account::query()->create([
            'type' => 'cash',
            'account_number' => '11101005',
            'account_name' => 'Petty Cash',
            'project' => '021C',
            'app_balance' => 10_000_000,
            'is_active' => true,
        ]);

        Account::query()->create([
            'type' => 'advance',
            'account_number' => '13101021',
            'account_name' => 'Advance Clearing',
            'project' => '021C',
            'app_balance' => 100_000_000,
            'is_active' => true,
        ]);
    }

    protected function seedVerificationJournalDocumentNumber(): void
    {
        DocumentNumber::query()->create([
            'document_type' => 'verification-journal',
            'project' => '021C',
            'year' => (int) date('Y'),
            'last_number' => 0,
        ]);
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

    /**
     * @return array<string, mixed>
     */
    protected function validStorePayload(?int $bilyetId = null): array
    {
        $payload = [
            'date' => now()->toDateString(),
            'bank_account' => '11201005',
            'description' => 'Cheque link test',
            'transaction_type' => 'transfer_to_petty_cash',
            'account_code' => ['11101005'],
            'debit_credit' => ['debit'],
            'detail_description' => ['PC top-up'],
            'project' => ['021C'],
            'cost_center' => ['30'],
            'amount' => [5_000_000],
        ];

        if ($bilyetId !== null) {
            $payload['bilyet_id'] = $bilyetId;
        }

        return $payload;
    }

    public function test_store_with_matching_bilyet_persists_bilyet_id(): void
    {
        $user = $this->createAuthorizedCashier();

        $this->actingAs($user)
            ->post(route('cashier.bank-transactions.store'), $this->validStorePayload($this->matchingBilyet->id))
            ->assertRedirect(route('cashier.bank-transactions.index'))
            ->assertSessionHas('success');

        $journal = VerificationJournal::query()->latest('id')->first();
        $this->assertNotNull($journal);
        $this->assertSame($this->matchingBilyet->id, $journal->bilyet_id);
    }

    public function test_store_without_bilyet_id_succeeds(): void
    {
        $user = $this->createAuthorizedCashier();

        $this->actingAs($user)
            ->post(route('cashier.bank-transactions.store'), $this->validStorePayload())
            ->assertRedirect(route('cashier.bank-transactions.index'))
            ->assertSessionHas('success');

        $journal = VerificationJournal::query()->latest('id')->first();
        $this->assertNotNull($journal);
        $this->assertNull($journal->bilyet_id);
    }

    public function test_store_rejects_bilyet_from_wrong_giro(): void
    {
        $user = $this->createAuthorizedCashier();

        $this->actingAs($user)
            ->post(route('cashier.bank-transactions.store'), $this->validStorePayload($this->otherBilyet->id))
            ->assertSessionHasErrors('bilyet_id');

        $this->assertSame(0, VerificationJournal::query()->count());
    }

    public function test_show_displays_cheque_or_dash(): void
    {
        $user = $this->createAuthorizedCashier();

        $withBilyet = VerificationJournal::query()->create([
            'nomor' => 'BT-CHEQUE-1',
            'date' => now()->toDateString(),
            'type' => 'bank',
            'project' => '021C',
            'bank_account' => '11201005',
            'bilyet_id' => $this->matchingBilyet->id,
            'description' => 'With cheque',
            'amount' => 5_000_000,
            'created_by' => $user->id,
            'status' => 'draft',
        ]);

        $withoutBilyet = VerificationJournal::query()->create([
            'nomor' => 'BT-CHEQUE-2',
            'date' => now()->toDateString(),
            'type' => 'bank',
            'project' => '021C',
            'bank_account' => '11201005',
            'description' => 'No cheque',
            'amount' => 5_000_000,
            'created_by' => $user->id,
            'status' => 'draft',
        ]);

        $this->actingAs($user)
            ->get(route('cashier.bank-transactions.show', $withBilyet->id))
            ->assertOk()
            ->assertSee('Cheque / Bilyet', false)
            ->assertSee('JM130552', false)
            ->assertSee('onhand', false)
            ->assertSee(
                route('cashier.bilyets.history', $this->matchingBilyet->id),
                false
            );

        $this->actingAs($user)
            ->get(route('cashier.bank-transactions.show', $withoutBilyet->id))
            ->assertOk()
            ->assertSee('Cheque / Bilyet', false)
            ->assertDontSee('JM130552', false);
    }

    public function test_sap_journal_entry_payload_excludes_bilyet_id(): void
    {
        $user = $this->createAuthorizedCashier();

        $journal = VerificationJournal::query()->create([
            'nomor' => 'BT-SAP-PAYLOAD',
            'date' => now()->toDateString(),
            'type' => 'bank',
            'project' => '021C',
            'bank_account' => '11201005',
            'bilyet_id' => $this->matchingBilyet->id,
            'description' => 'SAP payload test',
            'amount' => 5_000_000,
            'created_by' => $user->id,
            'status' => 'draft',
        ]);

        VerificationJournalDetail::query()->create([
            'verification_journal_id' => $journal->id,
            'realization_date' => $journal->date,
            'account_code' => '11201005',
            'debit_credit' => 'credit',
            'description' => $journal->description,
            'project' => '021C',
            'cost_center' => '30',
            'amount' => 5_000_000,
        ]);

        VerificationJournalDetail::query()->create([
            'verification_journal_id' => $journal->id,
            'realization_date' => $journal->date,
            'account_code' => '11101005',
            'debit_credit' => 'debit',
            'description' => 'PC',
            'project' => '021C',
            'cost_center' => '30',
            'amount' => 5_000_000,
        ]);

        $payload = (new SapJournalEntryBuilder($journal))->build();
        $encoded = json_encode($payload);

        $this->assertIsString($encoded);
        $this->assertStringNotContainsString('bilyet', strtolower($encoded));
        $this->assertSame(['ReferenceDate', 'TaxDate', 'Memo', 'JournalEntryLines'], array_keys($payload));
    }

    public function test_direct_sap_eligibility_unchanged_with_bilyet_id(): void
    {
        $user = $this->createAuthorizedCashier();

        $journal = VerificationJournal::query()->create([
            'nomor' => 'BT-DIRECT-SAP',
            'date' => now()->toDateString(),
            'type' => 'bank',
            'project' => '021C',
            'bank_account' => '11201005',
            'bilyet_id' => $this->matchingBilyet->id,
            'description' => 'Direct SAP with bilyet',
            'amount' => 5_000_000,
            'created_by' => $user->id,
            'status' => 'draft',
            'validation_status' => VerificationJournal::VALIDATION_PENDING,
            'sap_submission_attempts' => 0,
        ]);

        VerificationJournalDetail::query()->create([
            'verification_journal_id' => $journal->id,
            'realization_date' => $journal->date,
            'account_code' => '11201005',
            'debit_credit' => 'credit',
            'description' => $journal->description,
            'project' => '021C',
            'cost_center' => '30',
            'amount' => 5_000_000,
        ]);

        VerificationJournalDetail::query()->create([
            'verification_journal_id' => $journal->id,
            'realization_date' => $journal->date,
            'account_code' => '11101005',
            'debit_credit' => 'debit',
            'description' => 'PC',
            'project' => '021C',
            'cost_center' => '30',
            'amount' => 5_000_000,
        ]);

        $service = app(CashierBankTransactionDirectSapService::class);
        $this->assertTrue($service->isEligibleForDirectSapSubmission($journal, $user));

        $this->mock(SapJournalSubmissionService::class, function ($mock) {
            $mock->shouldReceive('submit')
                ->once()
                ->andReturnUsing(function (VerificationJournal $vj) {
                    $vj->update([
                        'status' => 'posted',
                        'sap_journal_no' => 'SAP-BILYET-001',
                        'sap_submission_status' => 'success',
                    ]);

                    return [
                        'success' => true,
                        'sap_journal_no' => 'SAP-BILYET-001',
                        'message' => 'OK',
                    ];
                });
        });

        $this->actingAs($user)
            ->post(route('cashier.bank-transactions.submit', $journal->id))
            ->assertRedirect(route('cashier.bank-transactions.show', $journal->id))
            ->assertSessionHas('success');

        $journal->refresh();
        $this->assertSame('posted', $journal->status);
        $this->assertSame($this->matchingBilyet->id, $journal->bilyet_id);
        $this->assertSame(1, Incoming::query()->where('nomor', $journal->nomor)->count());
    }

    public function test_cashier_submit_permission_seeder_still_runs(): void
    {
        foreach ([81, 130, 141] as $userId) {
            User::factory()->create(['id' => $userId, 'project' => '021C', 'department_id' => $this->department->id]);
        }

        $this->seed(CashierSubmitVjToSapPermissionSeeder::class);

        $this->assertNotNull(Permission::query()->where('name', 'cashier_submit_vj_to_sap')->first());
    }
}
