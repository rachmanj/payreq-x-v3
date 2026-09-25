<?php

namespace Tests\Feature;

use App\Models\ApprovalPlan;
use App\Models\Department;
use App\Models\Payreq;
use App\Models\PayreqTransferDestination;
use App\Models\Realization;
use App\Models\RealizationDetail;
use App\Models\TransferAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ApprovalPayreqPaymentMethodDisplayTest extends TestCase
{
    use RefreshDatabase;

    private User $approver;

    private User $requestor;

    private Department $department;

    private int $bankId;

    private TransferAccount $transferAccount;

    protected function setUp(): void
    {
        parent::setUp();

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

        $this->requestor = User::factory()->create([
            'project' => '000H',
            'department_id' => $this->department->id,
        ]);

        $this->bankId = DB::table('banks')->insertGetId([
            'name' => 'Mandiri',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->transferAccount = TransferAccount::create([
            'user_id' => $this->requestor->id,
            'bank_id' => $this->bankId,
            'account_number' => '9876543210',
            'account_name' => 'Iwan Requestor',
            'label' => 'Rekening Utama',
        ]);
    }

    public function test_approval_payreq_show_displays_cash_without_transfer_destination_block(): void
    {
        $plan = $this->createApprovalPlan(paymentMethod: 'cash', transferAccountId: null, withDestinations: false);

        $response = $this->actingAs($this->approver)->get(
            route('approvals.request.payreqs.show', $plan->id)
        );

        $response->assertOk();
        $response->assertSee('id="payment-method-readonly"', false);
        $response->assertSee('vj-chip-neutral', false);
        $response->assertSee('Cash', false);
        $response->assertDontSee('id="single-transfer-destination-readonly"', false);
        $response->assertDontSee('id="transfer-destinations-readonly"', false);
        $response->assertDontSee('Rekening Tujuan', false);
    }

    public function test_approval_payreq_show_displays_single_transfer_destination(): void
    {
        $plan = $this->createApprovalPlan(
            paymentMethod: 'transfer',
            transferAccountId: $this->transferAccount->id,
            withDestinations: false
        );

        $response = $this->actingAs($this->approver)->get(
            route('approvals.request.payreqs.show', $plan->id)
        );

        $response->assertOk();
        $response->assertSee('vj-chip-info', false);
        $response->assertSee('Transfer', false);
        $response->assertSee('id="single-transfer-destination-readonly"', false);
        $response->assertSee('Rekening Utama', false);
        $response->assertSee('9876543210', false);
        $response->assertSee('Iwan Requestor', false);
        $response->assertSee('Mandiri', false);
        $response->assertDontSee('id="transfer-destinations-readonly"', false);
    }

    public function test_approval_payreq_show_multi_transfer_uses_existing_block_without_single_duplicate(): void
    {
        $plan = $this->createApprovalPlan(
            paymentMethod: 'transfer',
            transferAccountId: $this->transferAccount->id,
            withDestinations: true
        );

        $response = $this->actingAs($this->approver)->get(
            route('approvals.request.payreqs.show', $plan->id)
        );

        $response->assertOk();
        $response->assertSee('id="payment-method-readonly"', false);
        $response->assertSee('Transfer', false);
        $response->assertSee('id="transfer-destinations-readonly"', false);
        $response->assertSee('Daftar Tujuan Transfer', false);
        $response->assertDontSee('id="single-transfer-destination-readonly"', false);
    }

    public function test_approval_payreq_show_still_exposes_approval_form(): void
    {
        $plan = $this->createApprovalPlan(
            paymentMethod: 'transfer',
            transferAccountId: $this->transferAccount->id,
            withDestinations: false
        );

        $response = $this->actingAs($this->approver)->get(
            route('approvals.request.payreqs.show', $plan->id)
        );

        $response->assertOk();
        $response->assertSee('id="approvals-update"', false);
        $response->assertSee(route('approvals.plan.update', $plan->id), false);
        $response->assertSee('Approval for Payreq', false);
    }

    private function createApprovalPlan(
        string $paymentMethod,
        ?int $transferAccountId,
        bool $withDestinations
    ): ApprovalPlan {
        $payreq = Payreq::query()->create([
            'nomor' => 'PR-PAYMETHOD-'.uniqid(),
            'user_id' => $this->requestor->id,
            'project' => '000H',
            'department_id' => $this->department->id,
            'amount' => 500000,
            'status' => 'submitted',
            'type' => 'reimburse',
            'payment_method' => $paymentMethod,
            'transfer_account_id' => $transferAccountId,
            'editable' => '0',
            'deletable' => '0',
            'submit_at' => now(),
        ]);

        $realization = Realization::query()->create([
            'nomor' => 'RLZ-'.uniqid(),
            'payreq_id' => $payreq->id,
            'user_id' => $this->requestor->id,
            'project' => '000H',
            'department_id' => $this->department->id,
            'status' => 'reimburse-submitted',
            'editable' => '0',
            'deletable' => '0',
            'submit_at' => now(),
        ]);

        RealizationDetail::query()->create([
            'realization_id' => $realization->id,
            'description' => 'Biaya',
            'amount' => 500000,
            'project' => '000H',
            'department_id' => $this->department->id,
        ]);

        if ($withDestinations) {
            PayreqTransferDestination::query()->create([
                'payreq_id' => $payreq->id,
                'transfer_account_id' => $this->transferAccount->id,
                'planned_amount' => 500000,
                'remark' => 'Satu tujuan',
                'created_by' => $this->requestor->id,
            ]);
        }

        return ApprovalPlan::query()->create([
            'document_id' => $payreq->id,
            'document_type' => 'payreq',
            'approver_id' => $this->approver->id,
            'status' => 0,
            'is_open' => 1,
        ]);
    }
}
