<?php

namespace Tests\Feature;

use App\Models\ApprovalPlan;
use App\Models\DocumentNumber;
use App\Models\Payreq;
use App\Models\Realization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApprovalDecideCommandTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;

    private User $otherApprover;

    private User $requestor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actor = User::factory()->create([
            'username' => 'rachmanj',
            'project' => '000H',
        ]);

        $this->otherApprover = User::factory()->create([
            'project' => '000H',
        ]);

        $this->requestor = User::factory()->create([
            'project' => '000H',
        ]);

        DocumentNumber::create([
            'document_type' => 'payreq',
            'project' => '000H',
            'year' => date('Y'),
            'last_number' => 100,
        ]);
    }

    public function test_command_is_registered(): void
    {
        $this->artisan('approval:decide', [
            'plan_id' => 99999,
            '--decision' => 'approve',
        ])->assertFailed()
            ->expectsOutputToContain('tidak ditemukan');
    }

    public function test_approve_single_plan_of_multi_approver_document_keeps_document_pending(): void
    {
        $payreq = Payreq::create([
            'user_id' => $this->requestor->id,
            'nomor' => 'DRAFT-MULTI',
            'type' => 'advance',
            'amount' => 500000,
            'remarks' => 'Multi approver test',
            'project' => '000H',
            'status' => 'submitted',
            'editable' => 0,
            'deletable' => 0,
        ]);

        $planOne = ApprovalPlan::create([
            'document_id' => $payreq->id,
            'document_type' => 'payreq',
            'approver_id' => $this->actor->id,
            'status' => 0,
            'is_open' => 1,
        ]);

        ApprovalPlan::create([
            'document_id' => $payreq->id,
            'document_type' => 'payreq',
            'approver_id' => $this->otherApprover->id,
            'status' => 0,
            'is_open' => 1,
        ]);

        $this->artisan('approval:decide', [
            'plan_id' => $planOne->id,
            '--decision' => 'approve',
        ])->assertSuccessful();

        $planOne->refresh();
        $payreq->refresh();

        $this->assertSame(1, $planOne->status);
        $this->assertSame('submitted', $payreq->status);
        $this->assertSame(0, (int) $payreq->printable);
    }

    public function test_approve_all_plans_approves_advance_payreq_and_updates_linked_payreq_for_realization(): void
    {
        $payreq = Payreq::create([
            'user_id' => $this->requestor->id,
            'nomor' => 'DRAFT-FULL',
            'type' => 'advance',
            'amount' => 750000,
            'remarks' => 'Full approval test',
            'project' => '000H',
            'status' => 'submitted',
            'editable' => 0,
            'deletable' => 0,
        ]);

        $planOne = ApprovalPlan::create([
            'document_id' => $payreq->id,
            'document_type' => 'payreq',
            'approver_id' => $this->actor->id,
            'status' => 0,
            'is_open' => 1,
        ]);

        $planTwo = ApprovalPlan::create([
            'document_id' => $payreq->id,
            'document_type' => 'payreq',
            'approver_id' => $this->otherApprover->id,
            'status' => 0,
            'is_open' => 1,
        ]);

        $this->artisan('approval:decide', [
            'plan_id' => $planOne->id,
            '--decision' => 'approve',
        ])->assertSuccessful();

        $this->artisan('approval:decide', [
            'plan_id' => $planTwo->id,
            '--decision' => 'approve',
        ])->assertSuccessful();

        $payreq->refresh();

        $this->assertSame('approved', $payreq->status);
        $this->assertSame(1, (int) $payreq->printable);
        $this->assertSame(0, (int) $payreq->editable);
        $this->assertSame('DRAFT-FULL', $payreq->draft_no);
        $this->assertNotSame('DRAFT-FULL', $payreq->nomor);
        $this->assertNotNull($payreq->approved_at);

        $linkedPayreq = Payreq::create([
            'user_id' => $this->requestor->id,
            'nomor' => 'PR-LINK',
            'type' => 'advance',
            'amount' => 300000,
            'remarks' => 'Linked payreq',
            'project' => '000H',
            'status' => 'paid',
        ]);

        $realization = Realization::create([
            'nomor' => 'RZ-001',
            'payreq_id' => $linkedPayreq->id,
            'user_id' => $this->requestor->id,
            'project' => '000H',
            'remarks' => 'Realization approval',
            'status' => 'submitted',
            'editable' => 0,
            'deletable' => 0,
        ]);

        $realPlanOne = ApprovalPlan::create([
            'document_id' => $realization->id,
            'document_type' => 'realization',
            'approver_id' => $this->actor->id,
            'status' => 0,
            'is_open' => 1,
        ]);

        $realPlanTwo = ApprovalPlan::create([
            'document_id' => $realization->id,
            'document_type' => 'realization',
            'approver_id' => $this->otherApprover->id,
            'status' => 0,
            'is_open' => 1,
        ]);

        $this->artisan('approval:decide', [
            'plan_id' => $realPlanOne->id,
            '--decision' => 'approve',
        ])->assertSuccessful();

        $this->artisan('approval:decide', [
            'plan_id' => $realPlanTwo->id,
            '--decision' => 'approve',
        ])->assertSuccessful();

        $linkedPayreq->refresh();
        $this->assertSame('realization', $linkedPayreq->status);
    }

    public function test_revise_closes_all_open_approval_plans(): void
    {
        $payreq = Payreq::create([
            'user_id' => $this->requestor->id,
            'nomor' => 'DRAFT-REV',
            'type' => 'advance',
            'amount' => 200000,
            'remarks' => 'Revise test',
            'project' => '000H',
            'status' => 'submitted',
            'editable' => 0,
            'deletable' => 0,
        ]);

        $planOne = ApprovalPlan::create([
            'document_id' => $payreq->id,
            'document_type' => 'payreq',
            'approver_id' => $this->actor->id,
            'status' => 0,
            'is_open' => 1,
        ]);

        $planTwo = ApprovalPlan::create([
            'document_id' => $payreq->id,
            'document_type' => 'payreq',
            'approver_id' => $this->otherApprover->id,
            'status' => 0,
            'is_open' => 1,
        ]);

        $this->artisan('approval:decide', [
            'plan_id' => $planOne->id,
            '--decision' => 'revise',
            '--remarks' => 'Perlu revisi',
        ])->assertSuccessful();

        $payreq->refresh();
        $planOne->refresh();
        $planTwo->refresh();

        $this->assertSame('revise', $payreq->status);
        $this->assertSame(1, (int) $payreq->editable);
        $this->assertSame(1, (int) $payreq->deletable);
        $this->assertSame(0, (int) $planOne->is_open);
        $this->assertSame(0, (int) $planTwo->is_open);
    }

    public function test_reject_marks_document_rejected_and_deletable(): void
    {
        $payreq = Payreq::create([
            'user_id' => $this->requestor->id,
            'nomor' => 'DRAFT-REJ',
            'type' => 'advance',
            'amount' => 150000,
            'remarks' => 'Reject test',
            'project' => '000H',
            'status' => 'submitted',
            'editable' => 0,
            'deletable' => 0,
        ]);

        $plan = ApprovalPlan::create([
            'document_id' => $payreq->id,
            'document_type' => 'payreq',
            'approver_id' => $this->actor->id,
            'status' => 0,
            'is_open' => 1,
        ]);

        $this->artisan('approval:decide', [
            'plan_id' => $plan->id,
            '--decision' => 'reject',
            '--remarks' => 'Ditolak',
        ])->assertSuccessful();

        $payreq->refresh();
        $plan->refresh();

        $this->assertSame('rejected', $payreq->status);
        $this->assertSame(1, (int) $payreq->deletable);
        $this->assertSame(0, (int) $plan->is_open);
    }

    public function test_plan_not_found_exits_with_failure(): void
    {
        $this->artisan('approval:decide', [
            'plan_id' => 999999,
            '--decision' => 'approve',
        ])->assertFailed();
    }

    public function test_already_decided_plan_exits_with_failure(): void
    {
        $payreq = Payreq::create([
            'user_id' => $this->requestor->id,
            'nomor' => 'DRAFT-DONE',
            'type' => 'advance',
            'amount' => 100000,
            'remarks' => 'Already decided',
            'project' => '000H',
            'status' => 'submitted',
        ]);

        $plan = ApprovalPlan::create([
            'document_id' => $payreq->id,
            'document_type' => 'payreq',
            'approver_id' => $this->actor->id,
            'status' => 1,
            'is_open' => 1,
        ]);

        $this->artisan('approval:decide', [
            'plan_id' => $plan->id,
            '--decision' => 'approve',
        ])->assertFailed();
    }

    public function test_approve_rab_via_command_keeps_usage_intact(): void
    {
        $department = \App\Models\Department::create([
            'department_name' => 'Test Department',
            'akronim' => 'TD',
            'sap_code' => 'TD',
        ]);

        $anggaran = \App\Models\Anggaran::create([
            'nomor' => 'RAB-TEST-001',
            'project' => '000H',
            'department_id' => $department->id,
            'type' => 'operational',
            'amount' => 10000000,
            'balance' => 10000000,
            'usage' => 'user',
            'status' => 'submitted',
            'created_by' => $this->requestor->id,
            'submit_at' => now(),
            'editable' => 0,
            'deletable' => 0,
        ]);

        $plan = ApprovalPlan::create([
            'document_id' => $anggaran->id,
            'document_type' => 'rab',
            'approver_id' => $this->actor->id,
            'status' => 0,
            'is_open' => 1,
        ]);

        $this->artisan('approval:decide', [
            'plan_id' => $plan->id,
            '--decision' => 'approve',
        ])->assertSuccessful();

        $anggaran->refresh();
        $plan->refresh();

        $this->assertSame(1, $plan->status);
        $this->assertSame('approved', $anggaran->status);
        $this->assertSame('user', $anggaran->usage, 'usage harus tetap utuh, tidak di-null-kan');
        $this->assertSame(1, (int) $anggaran->printable);
        $this->assertSame(0, (int) $anggaran->editable);
    }
}
