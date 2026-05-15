<?php

namespace Tests\Feature;

use App\Models\ApprovalPlan;
use App\Models\Payreq;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ApproverRequestorReplyInboxTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(
            ['name' => 'akses_approval_request'],
            ['guard_name' => 'web'],
        );
    }

    public function test_inbox_shows_unread_after_requestor_replies_and_clears_after_conversation(): void
    {
        $approver = User::factory()->create();
        $approver->givePermissionTo('akses_approval_request');

        $requestor = User::factory()->create();

        $payreq = Payreq::create([
            'user_id' => $requestor->id,
            'nomor' => 'TEST-001',
            'type' => 'advance',
            'amount' => 1000,
            'remarks' => 'Purpose',
            'project' => 'TEST',
            'status' => 'approved',
        ]);

        $plan = ApprovalPlan::create([
            'document_id' => $payreq->id,
            'document_type' => 'payreq',
            'approver_id' => $approver->id,
            'status' => 1,
            'remarks' => 'Please provide supporting documents',
            'is_open' => 0,
            'is_read' => 1,
        ]);

        $this->actingAs($requestor)
            ->putJson(route('approvals.plan.requestor-remarks.update', $plan->id), [
                'requestor_remarks' => 'Attached as requested.',
            ])
            ->assertOk()
            ->assertJsonFragment(['success' => true]);

        $plan->refresh();
        $this->assertNotNull($plan->requestor_remarks_updated_at);
        $this->assertTrue($plan->isApproverUnreadRequestorReply());

        $this->actingAs($approver)
            ->getJson(route('approvals.plan.requestor-replies.inbox'))
            ->assertOk()
            ->assertJsonPath('unread_count', 1);

        $this->actingAs($approver)
            ->get(route('approvals.plan.conversation', $plan->id))
            ->assertOk();

        $plan->refresh();
        $this->assertFalse($plan->isApproverUnreadRequestorReply());

        $this->actingAs($approver)
            ->getJson(route('approvals.plan.requestor-replies.inbox'))
            ->assertOk()
            ->assertJsonPath('unread_count', 0);
    }

    public function test_user_without_permission_cannot_access_inbox(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson(route('approvals.plan.requestor-replies.inbox'))
            ->assertForbidden();
    }

    public function test_requestor_cannot_change_reply_after_first_save(): void
    {
        $approver = User::factory()->create();
        $requestor = User::factory()->create();

        $payreq = Payreq::create([
            'user_id' => $requestor->id,
            'nomor' => 'TEST-002',
            'type' => 'advance',
            'amount' => 1000,
            'remarks' => 'Purpose',
            'project' => 'TEST',
            'status' => 'approved',
        ]);

        $plan = ApprovalPlan::create([
            'document_id' => $payreq->id,
            'document_type' => 'payreq',
            'approver_id' => $approver->id,
            'status' => 1,
            'remarks' => 'Note from approver',
            'requestor_remarks' => 'Original reply',
            'requestor_remarks_updated_at' => now(),
            'is_open' => 0,
            'is_read' => 1,
        ]);

        $this->actingAs($requestor)
            ->putJson(route('approvals.plan.requestor-remarks.update', $plan->id), [
                'requestor_remarks' => 'Trying to edit',
            ])
            ->assertForbidden()
            ->assertJsonFragment(['message' => 'Your reply has already been submitted and cannot be changed.']);

        $plan->refresh();
        $this->assertSame('Original reply', $plan->requestor_remarks);
    }
}
