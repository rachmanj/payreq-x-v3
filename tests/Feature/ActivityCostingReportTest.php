<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Activity;
use App\Models\Department;
use App\Models\Payreq;
use App\Models\Realization;
use App\Models\RealizationDetail;
use App\Models\User;
use App\Models\VerificationJournal;
use App\Models\VerificationJournalDetail;
use App\Services\VerificationJournalAggregator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ActivityCostingReportTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Department $department;

    protected Department $otherDepartment;

    protected Account $cashAccount;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'view_activity_costing', 'guard_name' => 'web']);
        Role::query()->firstOrCreate(['name' => 'acc-team', 'guard_name' => 'web']);

        $this->user = User::factory()->create(['project' => '000H']);
        $this->user->assignRole('acc-team');
        $this->user->givePermissionTo('view_activity_costing');

        $this->department = Department::query()->create([
            'department_name' => 'Finance',
            'akronim' => 'FIN',
            'sap_code' => 'FIN-01',
            'is_active' => true,
            'is_selectable' => true,
        ]);

        $this->otherDepartment = Department::query()->create([
            'department_name' => 'Operations',
            'akronim' => 'OPS',
            'sap_code' => 'OPS-01',
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

    protected function createActivity(array $overrides = []): Activity
    {
        return Activity::query()->create(array_merge([
            'code' => 'KEG-2026-'.uniqid(),
            'name' => 'Test Activity',
            'periode' => '2026-09',
            'project' => '000H',
            'department_id' => $this->department->id,
            'mode' => 'tanpa_reklasifikasi',
            'status' => 'open',
            'created_by' => $this->user->id,
        ], $overrides));
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
        int $amount,
        string $description,
        ?int $activityId = null,
        bool $activityExcluded = false,
        ?string $expenseDate = '2026-09-05',
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
            'expense_date' => $expenseDate,
        ]);
    }

    protected function seedMixedCostingData(): array
    {
        $meals = $this->createExpenseAccount('61201001', 'Meals');
        $security = $this->createExpenseAccount('61202001', 'Security');
        $office = $this->createExpenseAccount('61203001', 'Office');
        $mobilisation = $this->createExpenseAccount('51112001', 'Mobilisation');

        $activityReklas = $this->createActivity([
            'code' => 'KEG-2026-101',
            'name' => 'Mobilisasi Camp',
            'mode' => 'reklasifikasi',
            'account_id' => $mobilisation->id,
        ]);

        $activityTanpa = $this->createActivity([
            'code' => 'KEG-2026-102',
            'name' => 'HUT ARKA',
            'mode' => 'tanpa_reklasifikasi',
            'project' => '001H',
            'department_id' => $this->otherDepartment->id,
        ]);

        $activityEmpty = $this->createActivity([
            'code' => 'KEG-2026-103',
            'name' => 'Kegiatan Kosong',
        ]);

        $realizationA = $this->createRealization($activityReklas->id);
        $this->createDetail($realizationA, $meals, 150000, 'Meals crew', null, false, '2026-09-01');
        $this->createDetail($realizationA, $security, 250000, 'Pengawalan', null, false, '2026-09-02');
        $this->createDetail($realizationA, $office, 50000, 'ATK rutin', null, true, '2026-09-03');

        $realizationB = $this->createRealization($activityTanpa->id);
        $this->createDetail($realizationB, $meals, 80000, 'Snack', null, false, '2026-09-10');

        $realizationNoActivity = $this->createRealization();
        $this->createDetail($realizationNoActivity, $office, 99999, 'Tanpa kegiatan', null, false, '2026-09-15');

        $vj = VerificationJournal::query()->create([
            'nomor' => 'VJ-TEST-001',
            'project' => '000H',
            'date' => '2026-09-05',
            'amount' => 480000,
            'created_by' => $this->user->id,
        ]);

        $aggregator = app(VerificationJournalAggregator::class);
        $realizationA->update(['verification_journal_id' => $vj->id]);
        $realizationB->update(['verification_journal_id' => $vj->id]);

        $realizations = Realization::query()
            ->whereIn('id', [$realizationA->id, $realizationB->id])
            ->with([
                'department',
                'activity',
                'realizationDetails.account',
                'realizationDetails.department',
                'realizationDetails.activity',
                'realizationDetails.realization.activity',
            ])
            ->get();

        $lines = $aggregator->aggregate($realizations, $vj->id, $this->user);
        $persistLines = $aggregator->stripInternalMetadata($lines);
        foreach ($persistLines as $line) {
            VerificationJournalDetail::query()->create($line);
        }

        return compact('activityReklas', 'activityTanpa', 'activityEmpty', 'vj');
    }

    public function test_index_totals_are_correct_for_mixed_activity_data(): void
    {
        $data = $this->seedMixedCostingData();

        $response = $this->actingAs($this->user)
            ->getJson(route('reports.activity-costing.data'));

        $response->assertOk();

        $rows = collect($response->json('data'))->keyBy('code');

        $this->assertSame('400.000', $rows[$data['activityReklas']->code]['total_cost']);
        $this->assertSame('1', $rows[$data['activityReklas']->code]['realization_count']);
        $this->assertSame('2', $rows[$data['activityReklas']->code]['note_count']);

        $this->assertSame('80.000', $rows[$data['activityTanpa']->code]['total_cost']);

        $this->assertSame('0', $rows[$data['activityEmpty']->code]['total_cost']);
    }

    public function test_filters_by_date_project_and_department(): void
    {
        $data = $this->seedMixedCostingData();

        $this->actingAs($this->user)
            ->getJson(route('reports.activity-costing.data', [
                'date_from' => '2026-09-10',
                'date_to' => '2026-09-30',
            ]))
            ->assertOk()
            ->assertJsonFragment(['code' => $data['activityTanpa']->code, 'total_cost' => '80.000'])
            ->assertJsonFragment(['code' => $data['activityReklas']->code, 'total_cost' => '0']);

        $this->actingAs($this->user)
            ->getJson(route('reports.activity-costing.data', [
                'project' => '001H',
            ]))
            ->assertOk()
            ->assertJsonFragment(['code' => $data['activityTanpa']->code])
            ->assertJsonMissing(['code' => $data['activityReklas']->code]);

        $this->actingAs($this->user)
            ->getJson(route('reports.activity-costing.data', [
                'department_id' => $this->otherDepartment->id,
            ]))
            ->assertOk()
            ->assertJsonFragment(['code' => $data['activityTanpa']->code])
            ->assertJsonMissing(['code' => $data['activityReklas']->code]);
    }

    public function test_show_data_returns_notas_and_journal_lines(): void
    {
        $data = $this->seedMixedCostingData();
        $activity = $data['activityReklas'];

        $notaResponse = $this->actingAs($this->user)
            ->getJson(route('reports.activity-costing.show-data', [
                'id' => $activity->id,
                'type' => 'notas',
            ]));

        $notaResponse->assertOk();
        $this->assertCount(2, $notaResponse->json('data'));
        $this->assertStringContainsString('Meals crew', json_encode($notaResponse->json('data')));

        $journalResponse = $this->actingAs($this->user)
            ->getJson(route('reports.activity-costing.show-data', [
                'id' => $activity->id,
                'type' => 'journals',
            ]));

        $journalResponse->assertOk();
        $this->assertGreaterThanOrEqual(1, count($journalResponse->json('data')));
        $this->assertStringContainsString('Mobilisasi Camp', json_encode($journalResponse->json('data')));
    }

    public function test_guest_and_unauthorized_users_cannot_access_report(): void
    {
        $this->get(route('reports.activity-costing.index'))->assertRedirect(route('login'));

        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('reports.activity-costing.index'));
        $this->assertNotEquals(200, $response->getStatusCode());
    }

    public function test_export_downloads_excel_file(): void
    {
        Excel::fake();
        $this->seedMixedCostingData();

        $this->actingAs($this->user)
            ->get(route('reports.activity-costing.export'))
            ->assertOk();

        Excel::assertDownloaded('biaya-per-kegiatan-'.now()->format('Y-m-d').'.xlsx');
    }

    public function test_activity_id_is_persisted_on_verification_journal_details(): void
    {
        $data = $this->seedMixedCostingData();

        $this->assertDatabaseHas('verification_journal_details', [
            'verification_journal_id' => $data['vj']->id,
            'activity_id' => $data['activityReklas']->id,
            'debit_credit' => 'debit',
        ]);
    }
}
