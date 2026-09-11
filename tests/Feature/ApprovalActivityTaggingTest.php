<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Activity;
use App\Models\ApprovalPlan;
use App\Models\Department;
use App\Models\Payreq;
use App\Models\Realization;
use App\Models\RealizationDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ApprovalActivityTaggingTest extends TestCase
{
    use RefreshDatabase;

    protected User $approver;

    protected User $plainUser;

    protected Department $department;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'manage_activities', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'edit-submitted-realization', 'guard_name' => 'web']);

        Role::query()->firstOrCreate(['name' => 'approver', 'guard_name' => 'web']);

        $this->department = Department::query()->create([
            'department_name' => 'Finance',
            'akronim' => 'FIN',
            'sap_code' => 'FIN-01',
            'is_active' => true,
            'is_selectable' => true,
        ]);

        $this->approver = User::factory()->create([
            'project' => '000H',
            'department_id' => $this->department->id,
        ]);
        $this->approver->assignRole('approver');
        $this->approver->givePermissionTo(['manage_activities', 'edit-submitted-realization']);

        $this->plainUser = User::factory()->create([
            'project' => '000H',
            'department_id' => $this->department->id,
        ]);
        $this->plainUser->givePermissionTo('edit-submitted-realization');
    }

    protected function expenseAccount(): Account
    {
        return Account::query()->create([
            'account_number' => '51112001',
            'account_name' => 'Mobilisation',
            'type' => 'expense',
            'project' => '000H',
            'is_active' => true,
            'is_hidden' => false,
        ]);
    }

    protected function createOpenActivity(): Activity
    {
        return Activity::query()->create([
            'code' => 'KEG-2026-010',
            'name' => 'Mobilisasi Project X',
            'periode' => '2026-09',
            'mode' => 'reklasifikasi',
            'status' => 'open',
            'account_id' => $this->expenseAccount()->id,
            'project' => '000H',
            'created_by' => $this->approver->id,
        ]);
    }

    /**
     * @return array{plan: ApprovalPlan, realization: Realization, detail: RealizationDetail}
     */
    protected function createRealizationApprovalFixture(string $payreqType = 'advance'): array
    {
        $requestor = User::factory()->create([
            'project' => '000H',
            'department_id' => $this->department->id,
        ]);

        $payreq = Payreq::query()->create([
            'nomor' => 'PR-'.uniqid(),
            'user_id' => $requestor->id,
            'project' => '000H',
            'department_id' => $this->department->id,
            'amount' => 500000,
            'status' => 'submitted',
            'type' => $payreqType,
            'editable' => '0',
            'deletable' => '0',
            'submit_at' => now(),
        ]);

        $realization = Realization::query()->create([
            'nomor' => 'RLZ-'.uniqid(),
            'payreq_id' => $payreq->id,
            'user_id' => $requestor->id,
            'project' => '000H',
            'department_id' => $this->department->id,
            'status' => $payreqType === 'reimburse' ? 'reimburse-submitted' : 'submitted',
            'editable' => '0',
            'deletable' => '0',
            'submit_at' => now(),
        ]);

        $detail = RealizationDetail::query()->create([
            'realization_id' => $realization->id,
            'description' => 'Biaya transport',
            'amount' => 500000,
            'project' => '000H',
            'department_id' => $this->department->id,
        ]);

        $documentType = $payreqType === 'reimburse' ? 'payreq' : 'realization';
        $documentId = $payreqType === 'reimburse' ? $payreq->id : $realization->id;

        $plan = ApprovalPlan::query()->create([
            'document_id' => $documentId,
            'document_type' => $documentType,
            'approver_id' => $this->approver->id,
            'status' => 0,
            'is_open' => 1,
        ]);

        return compact('plan', 'realization', 'detail');
    }

    public function test_approver_can_save_activity_per_row_and_header_via_update_details(): void
    {
        $activityHeader = $this->createOpenActivity();
        $activityRow = Activity::query()->create([
            'code' => 'KEG-2026-011',
            'name' => 'Kegiatan Baris',
            'periode' => '2026-09',
            'mode' => 'tanpa_reklasifikasi',
            'status' => 'open',
            'project' => '000H',
            'created_by' => $this->approver->id,
        ]);

        $fixture = $this->createRealizationApprovalFixture('advance');

        $response = $this->actingAs($this->approver)->putJson(
            route('approvals.request.realizations.update-details', $fixture['plan']->id),
            [
                'activity_id' => $activityHeader->id,
                'details' => [[
                    'id' => $fixture['detail']->id,
                    'description' => 'Biaya transport',
                    'amount' => 500000,
                    'activity_id' => $activityRow->id,
                    'activity_excluded' => 0,
                ]],
            ]
        );

        $response->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('realizations', [
            'id' => $fixture['realization']->id,
            'activity_id' => $activityHeader->id,
        ]);

        $this->assertDatabaseHas('realization_details', [
            'id' => $fixture['detail']->id,
            'activity_id' => $activityRow->id,
            'activity_excluded' => false,
        ]);
    }

    public function test_user_without_manage_activities_permission_does_not_see_activity_elements(): void
    {
        $fixture = $this->createRealizationApprovalFixture('advance');

        $response = $this->actingAs($this->plainUser)->get(
            route('approvals.request.realizations.show', $fixture['plan']->id)
        );

        $response->assertOk();
        $response->assertDontSee('id="header_activity_id"', false);
        $response->assertDontSee('>Kegiatan</th>', false);
        $response->assertDontSee('Buat Kegiatan Baru', false);
    }

    public function test_activity_changes_rejected_when_approval_plan_already_approved(): void
    {
        $activity = $this->createOpenActivity();
        $fixture = $this->createRealizationApprovalFixture('advance');
        $fixture['plan']->update(['status' => 1]);

        $response = $this->actingAs($this->approver)->putJson(
            route('approvals.request.realizations.update-details', $fixture['plan']->id),
            [
                'activity_id' => $activity->id,
                'details' => [[
                    'id' => $fixture['detail']->id,
                    'description' => 'Biaya transport',
                    'amount' => 500000,
                    'activity_id' => $activity->id,
                ]],
            ]
        );

        $response->assertForbidden()
            ->assertJsonPath('message', 'Perubahan kegiatan tidak diizinkan karena dokumen sudah disetujui.');

        $this->assertDatabaseMissing('realizations', [
            'id' => $fixture['realization']->id,
            'activity_id' => $activity->id,
        ]);
    }

    public function test_advance_payreq_approval_ignores_activity_fields(): void
    {
        $activity = $this->createOpenActivity();
        $fixture = $this->createRealizationApprovalFixture('advance');

        $payreqFixture = Payreq::query()->create([
            'nomor' => 'PR-ADV-'.uniqid(),
            'user_id' => $fixture['realization']->user_id,
            'project' => '000H',
            'department_id' => $this->department->id,
            'amount' => 300000,
            'status' => 'submitted',
            'type' => 'advance',
            'editable' => '0',
            'deletable' => '0',
            'submit_at' => now(),
        ]);

        $advanceRealization = Realization::query()->create([
            'nomor' => 'RLZ-ADV-'.uniqid(),
            'payreq_id' => $payreqFixture->id,
            'user_id' => $fixture['realization']->user_id,
            'project' => '000H',
            'department_id' => $this->department->id,
            'status' => 'submitted',
            'editable' => '0',
            'deletable' => '0',
            'submit_at' => now(),
        ]);

        $advanceDetail = RealizationDetail::query()->create([
            'realization_id' => $advanceRealization->id,
            'description' => 'Advance item',
            'amount' => 300000,
            'project' => '000H',
            'department_id' => $this->department->id,
        ]);

        $advancePlan = ApprovalPlan::query()->create([
            'document_id' => $payreqFixture->id,
            'document_type' => 'payreq',
            'approver_id' => $this->approver->id,
            'status' => 0,
            'is_open' => 1,
        ]);

        $response = $this->actingAs($this->approver)->putJson(
            route('approvals.request.payreqs.update-details', $advancePlan->id),
            [
                'activity_id' => $activity->id,
                'details' => [[
                    'id' => $advanceDetail->id,
                    'description' => 'Advance item',
                    'amount' => 300000,
                    'activity_id' => $activity->id,
                    'activity_excluded' => 1,
                ]],
            ]
        );

        $response->assertOk();

        $this->assertDatabaseMissing('realizations', [
            'id' => $advanceRealization->id,
            'activity_id' => $activity->id,
        ]);

        $this->assertDatabaseHas('realization_details', [
            'id' => $advanceDetail->id,
            'activity_id' => null,
            'activity_excluded' => false,
        ]);
    }

    public function test_user_realization_page_does_not_show_activity_elements(): void
    {
        $requestor = User::factory()->create([
            'project' => '000H',
            'department_id' => $this->department->id,
        ]);

        $payreq = Payreq::query()->create([
            'nomor' => 'PR-USER-'.uniqid(),
            'user_id' => $requestor->id,
            'project' => '000H',
            'department_id' => $this->department->id,
            'amount' => 100000,
            'status' => 'open',
            'type' => 'advance',
            'editable' => '1',
            'deletable' => '1',
        ]);

        $realization = Realization::query()->create([
            'nomor' => 'RLZ-USER-'.uniqid(),
            'payreq_id' => $payreq->id,
            'user_id' => $requestor->id,
            'project' => '000H',
            'department_id' => $this->department->id,
            'status' => 'draft',
            'editable' => '1',
            'deletable' => '1',
        ]);

        $response = $this->actingAs($requestor)->get(
            route('user-payreqs.realizations.add_details', $realization->id)
        );

        $response->assertOk();
        $response->assertDontSee('Kegiatan (opsional)', false);
        $response->assertDontSee('id="header_activity_id"', false);
        $response->assertDontSee('>Kegiatan</th>', false);
    }
}
