<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Activity;
use App\Models\Department;
use App\Models\Payreq;
use App\Models\Realization;
use App\Models\RealizationDetail;
use App\Models\User;
use App\Services\VerificationJournalAggregator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerificationJournalAggregationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Department $department;

    protected Account $cashAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['project' => '000H']);
        $this->department = Department::query()->create([
            'department_name' => 'Finance',
            'akronim' => 'FIN',
            'sap_code' => 'FIN-01',
            'is_active' => true,
            'is_selectable' => true,
        ]);

        $this->cashAccount = Account::query()->create([
            'account_number' => '11010001',
            'account_name' => 'Cash HO',
            'type' => 'cash',
            'project' => '000H',
            'is_active' => true,
            'is_hidden' => false,
        ]);
    }

    protected function createExpenseAccount(string $number, string $name = 'Expense'): Account
    {
        return Account::query()->create([
            'account_number' => $number,
            'account_name' => $name,
            'type' => 'expense',
            'project' => '000H',
            'is_active' => true,
            'is_hidden' => false,
        ]);
    }

    protected function createAssetAccount(string $number): Account
    {
        return Account::query()->create([
            'account_number' => $number,
            'account_name' => 'Asset Account',
            'type' => 'asset',
            'project' => '000H',
            'is_active' => true,
            'is_hidden' => false,
        ]);
    }

    protected function createPayreq(): Payreq
    {
        return Payreq::query()->create([
            'nomor' => 'PR-'.uniqid(),
            'user_id' => $this->user->id,
            'project' => '000H',
            'department_id' => $this->department->id,
            'amount' => 500000,
            'status' => 'paid',
            'type' => 'advance',
        ]);
    }

    protected function createRealization(?int $activityId = null): Realization
    {
        return Realization::query()->create([
            'nomor' => 'RLZ-'.uniqid(),
            'payreq_id' => $this->createPayreq()->id,
            'user_id' => $this->user->id,
            'project' => '000H',
            'department_id' => $this->department->id,
            'activity_id' => $activityId,
            'status' => 'verification-complete',
        ]);
    }

    protected function createDetail(
        Realization $realization,
        Account $account,
        float $amount,
        string $description,
        ?int $activityId = null,
        bool $activityExcluded = false,
    ): RealizationDetail {
        return RealizationDetail::query()->create([
            'realization_id' => $realization->id,
            'project' => '000H',
            'department_id' => $this->department->id,
            'account_id' => $account->id,
            'amount' => $amount,
            'description' => $description,
            'activity_id' => $activityId,
            'activity_excluded' => $activityExcluded,
        ]);
    }

    protected function loadRealization(Realization $realization): Realization
    {
        return Realization::query()
            ->with([
                'department',
                'activity',
                'realizationDetails.account',
                'realizationDetails.department',
                'realizationDetails.activity',
                'realizationDetails.realization.activity',
            ])
            ->findOrFail($realization->id);
    }

    protected function aggregateRealizations(array $realizations, int $vjId = 1): array
    {
        $loaded = collect($realizations)->map(fn (Realization $r) => $this->loadRealization($r));

        return app(VerificationJournalAggregator::class)->aggregate($loaded, $vjId, $this->user);
    }

    public function test_reklasifikasi_activity_aggregates_multiple_expense_accounts(): void
    {
        $meals = $this->createExpenseAccount('61201001', 'Meals');
        $security = $this->createExpenseAccount('61202001', 'Security');
        $mobilisation = $this->createExpenseAccount('51112001', 'Mobilisation');

        $activity = Activity::query()->create([
            'code' => 'KEG-2026-001',
            'name' => 'Mobilisasi Excavator ke Project X',
            'periode' => '2026-09',
            'mode' => 'reklasifikasi',
            'account_id' => $mobilisation->id,
            'status' => 'open',
            'created_by' => $this->user->id,
        ]);

        $realization = $this->createRealization($activity->id);
        $this->createDetail($realization, $meals, 150000, 'Meals crew');
        $this->createDetail($realization, $security, 250000, 'Pengawalan');

        $lines = $this->aggregateRealizations([$realization]);

        $debits = collect($lines)->where('debit_credit', 'debit')->values();
        $credits = collect($lines)->where('debit_credit', 'credit')->values();

        $this->assertCount(1, $debits);
        $this->assertSame($mobilisation->account_number, $debits[0]['account_code']);
        $this->assertEquals(400000.0, (float) $debits[0]['amount']);
        $this->assertSame('Mobilisasi Excavator ke Project X', $debits[0]['description']);
        $this->assertTrue($debits[0]['is_reclassified']);

        $this->assertCount(1, $credits);
        $this->assertEquals(400000.0, (float) $credits[0]['amount']);
        $this->assertEquals(400000.0, $debits->sum('amount'));
        $this->assertEquals($debits->sum('amount'), $credits->sum('amount'));
    }

    public function test_tanpa_reklasifikasi_keeps_original_accounts_with_activity_name_memo(): void
    {
        $meals = $this->createExpenseAccount('61201001', 'Meals');
        $security = $this->createExpenseAccount('61202001', 'Security');

        $activity = Activity::query()->create([
            'code' => 'KEG-2026-002',
            'name' => 'Peringatan HUT ARKA',
            'periode' => '2026-09',
            'mode' => 'tanpa_reklasifikasi',
            'status' => 'open',
            'created_by' => $this->user->id,
        ]);

        $realization = $this->createRealization($activity->id);
        $this->createDetail($realization, $meals, 100000, 'Snack');
        $this->createDetail($realization, $security, 200000, 'Keamanan');

        $lines = $this->aggregateRealizations([$realization]);
        $debits = collect($lines)->where('debit_credit', 'debit')->values();

        $this->assertCount(2, $debits);
        $this->assertEqualsCanonicalizing(
            [$meals->account_number, $security->account_number],
            $debits->pluck('account_code')->all()
        );
        foreach ($debits as $debit) {
            $this->assertSame('Peringatan HUT ARKA', $debit['description']);
            $this->assertFalse($debit['is_reclassified']);
        }
    }

    public function test_mixed_activity_and_non_activity_lines_in_one_realization(): void
    {
        $meals = $this->createExpenseAccount('61201001', 'Meals');
        $office = $this->createExpenseAccount('61203001', 'Office');
        $mobilisation = $this->createExpenseAccount('51112001', 'Mobilisation');

        $activity = Activity::query()->create([
            'code' => 'KEG-2026-003',
            'name' => 'Mobilisasi Camp',
            'periode' => '2026-09',
            'mode' => 'reklasifikasi',
            'account_id' => $mobilisation->id,
            'status' => 'open',
            'created_by' => $this->user->id,
        ]);

        $realization = $this->createRealization($activity->id);
        $this->createDetail($realization, $meals, 100000, 'Meals mobilisasi');
        $this->createDetail($realization, $office, 50000, 'ATK rutin', null, true);

        $lines = $this->aggregateRealizations([$realization]);
        $debits = collect($lines)->where('debit_credit', 'debit')->values();

        $this->assertCount(2, $debits);

        $activityDebit = $debits->firstWhere('account_code', $mobilisation->account_number);
        $plainDebit = $debits->firstWhere('account_code', $office->account_number);

        $this->assertNotNull($activityDebit);
        $this->assertEquals(100000.0, (float) $activityDebit['amount']);
        $this->assertSame('Mobilisasi Camp', $activityDebit['description']);

        $this->assertNotNull($plainDebit);
        $this->assertEquals(50000.0, (float) $plainDebit['amount']);
        $this->assertSame('ATK rutin', $plainDebit['description']);
    }

    public function test_regression_without_activity_matches_legacy_per_nota_lines(): void
    {
        $accountA = $this->createExpenseAccount('61201001', 'Meals');
        $accountB = $this->createExpenseAccount('61202001', 'Transport');

        $realization = $this->createRealization();
        $this->createDetail($realization, $accountA, 75000, 'Lunch team');
        $this->createDetail($realization, $accountB, 125000, 'Taxi airport');

        $lines = $this->aggregateRealizations([$realization]);

        $debits = collect($lines)->where('debit_credit', 'debit')->values();
        $credits = collect($lines)->where('debit_credit', 'credit')->values();

        $this->assertCount(2, $debits);
        $this->assertCount(1, $credits);
        $this->assertEqualsCanonicalizing(
            ['Lunch team', 'Taxi airport'],
            $debits->pluck('description')->all()
        );
        $this->assertEqualsCanonicalizing(
            [$accountA->account_number, $accountB->account_number],
            $debits->pluck('account_code')->all()
        );
        $this->assertEquals(200000.0, (float) $credits->sum('amount'));
        $this->assertEquals($debits->sum('amount'), $credits->sum('amount'));
    }

    public function test_non_expense_account_in_reklasifikasi_activity_is_not_reclassified(): void
    {
        $ppn = $this->createAssetAccount('11701001');
        $mobilisation = $this->createExpenseAccount('51112001', 'Mobilisation');

        $activity = Activity::query()->create([
            'code' => 'KEG-2026-004',
            'name' => 'Mobilisasi dengan PPN',
            'periode' => '2026-09',
            'mode' => 'reklasifikasi',
            'account_id' => $mobilisation->id,
            'status' => 'open',
            'created_by' => $this->user->id,
        ]);

        $realization = $this->createRealization($activity->id);
        $this->createDetail($realization, $ppn, 11000, 'PPN nota');

        $lines = $this->aggregateRealizations([$realization]);
        $debit = collect($lines)->firstWhere('debit_credit', 'debit');

        $this->assertSame($ppn->account_number, $debit['account_code']);
        $this->assertFalse($debit['is_reclassified']);
        $this->assertSame('Akun non-beban tidak direklasifikasi', $debit['reclassified_reason']);
    }
}
