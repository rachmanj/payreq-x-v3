<?php

namespace Tests\Feature;

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

class ApprovalOpenActivitiesFilterTest extends TestCase
{
    use RefreshDatabase;

    protected User $approver;

    protected Department $department;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'manage_activities', 'guard_name' => 'web']);
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
        $this->approver->givePermissionTo('manage_activities');
    }

    /**
     * @return array{plan: ApprovalPlan, realization: Realization}
     */
    protected function createRealizationApprovalFixture(array $detailProjects = ['000H']): array
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
            'type' => 'advance',
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
            'status' => 'submitted',
            'editable' => '0',
            'deletable' => '0',
            'submit_at' => now(),
        ]);

        foreach ($detailProjects as $index => $project) {
            RealizationDetail::query()->create([
                'realization_id' => $realization->id,
                'description' => 'Detail '.$index,
                'amount' => 250000,
                'project' => $project,
                'department_id' => $this->department->id,
            ]);
        }

        $plan = ApprovalPlan::query()->create([
            'document_id' => $realization->id,
            'document_type' => 'realization',
            'approver_id' => $this->approver->id,
            'status' => 0,
            'is_open' => 1,
        ]);

        return compact('plan', 'realization');
    }

    protected function createActivity(array $attributes): Activity
    {
        return Activity::query()->create(array_merge([
            'code' => 'KEG-'.uniqid(),
            'name' => 'Test Activity',
            'periode' => '2026-09',
            'mode' => 'reklasifikasi',
            'status' => 'open',
            'created_by' => $this->approver->id,
        ], $attributes));
    }

    public function test_realization_approval_show_includes_reklasifikasi_activity_for_detail_project(): void
    {
        $reklasActivity = $this->createActivity([
            'code' => 'KEG-2026-003',
            'name' => 'Reklasifikasi 022C',
            'project' => '022C',
        ]);

        $fixture = $this->createRealizationApprovalFixture(['000H', '022C']);

        $response = $this->actingAs($this->approver)->get(
            route('approvals.request.realizations.show', $fixture['plan']->id)
        );

        $response->assertOk();
        $response->assertSee('KEG-2026-003 — Reklasifikasi 022C', false);
        $response->assertSee('"id":'.$reklasActivity->id, false);
    }

    public function test_realization_approval_show_excludes_reklasifikasi_activity_without_matching_detail_project(): void
    {
        $this->createActivity([
            'code' => 'KEG-2026-003',
            'name' => 'Reklasifikasi 022C',
            'project' => '022C',
        ]);

        $fixture = $this->createRealizationApprovalFixture(['000H']);

        $response = $this->actingAs($this->approver)->get(
            route('approvals.request.realizations.show', $fixture['plan']->id)
        );

        $response->assertOk();
        $response->assertDontSee('KEG-2026-003 — Reklasifikasi 022C', false);
    }

    public function test_payreq_approval_show_includes_reklasifikasi_activity_for_detail_project(): void
    {
        $reklasActivity = $this->createActivity([
            'code' => 'KEG-2026-003',
            'name' => 'Reklasifikasi 022C',
            'project' => '022C',
        ]);

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
            'type' => 'reimburse',
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
            'status' => 'reimburse-submitted',
            'editable' => '0',
            'deletable' => '0',
            'submit_at' => now(),
        ]);

        RealizationDetail::query()->create([
            'realization_id' => $realization->id,
            'description' => 'Reklas detail',
            'amount' => 500000,
            'project' => '022C',
            'department_id' => $this->department->id,
        ]);

        $plan = ApprovalPlan::query()->create([
            'document_id' => $payreq->id,
            'document_type' => 'payreq',
            'approver_id' => $this->approver->id,
            'status' => 0,
            'is_open' => 1,
        ]);

        $response = $this->actingAs($this->approver)->get(
            route('approvals.request.payreqs.show', $plan->id)
        );

        $response->assertOk();
        $response->assertSee('KEG-2026-003 — Reklasifikasi 022C', false);
        $response->assertSee('"id":'.$reklasActivity->id, false);
    }

    public function test_realization_header_project_is_not_changed_by_open_activities_filter(): void
    {
        $fixture = $this->createRealizationApprovalFixture(['022C']);

        $this->actingAs($this->approver)->get(
            route('approvals.request.realizations.show', $fixture['plan']->id)
        );

        $this->assertDatabaseHas('realizations', [
            'id' => $fixture['realization']->id,
            'project' => '000H',
        ]);
    }
}
