<?php

namespace Tests\Feature;

use App\Models\ApprovalPlan;
use App\Models\Department;
use App\Models\Payreq;
use App\Models\Realization;
use App\Models\RealizationDetail;
use App\Models\TransferAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ApprovalPayreqWithoutRealizationTest extends TestCase
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

    public function test_advance_payreq_without_realization_show_returns_ok_with_payment_method(): void
    {
        $plan = $this->createAdvanceApprovalPlanWithoutRealization(paymentMethod: 'cash');

        $response = $this->actingAs($this->approver)->get(
            route('approvals.request.payreqs.show', $plan->id)
        );

        $response->assertOk();
        $response->assertSee('Payreq Info', false);
        $response->assertSee('id="payment-method-readonly"', false);
        $response->assertSee('Cash', false);
        $response->assertSee('Belum ada realization', false);
        $response->assertSee('id="approvals-update"', false);
    }

    public function test_advance_without_realization_transfer_shows_single_destination(): void
    {
        $plan = $this->createAdvanceApprovalPlanWithoutRealization(
            paymentMethod: 'transfer',
            transferAccountId: $this->transferAccount->id
        );

        $response = $this->actingAs($this->approver)->get(
            route('approvals.request.payreqs.show', $plan->id)
        );

        $response->assertOk();
        $response->assertSee('Transfer', false);
        $response->assertSee('id="single-transfer-destination-readonly"', false);
        $response->assertSee('Rekening Utama', false);
        $response->assertSee('9876543210', false);
        $response->assertSee('Iwan Requestor', false);
        $response->assertSee('Mandiri', false);
    }

    public function test_reimburse_payreq_with_realization_show_unchanged(): void
    {
        $plan = $this->createReimburseApprovalPlanWithRealization();

        $response = $this->actingAs($this->approver)->get(
            route('approvals.request.payreqs.show', $plan->id)
        );

        $response->assertOk();
        $response->assertSee('RLZ-WITH-REAL', false);
        $response->assertSee('Biaya operasional', false);
        $response->assertDontSee('Belum ada realization', false);
    }

    public function test_approve_payreq_without_realization_via_plan_update(): void
    {
        $plan = $this->createAdvanceApprovalPlanWithoutRealization(paymentMethod: 'cash');

        $response = $this->actingAs($this->approver)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->put(route('approvals.plan.update', $plan->id), [
                'status' => 1,
                'remarks' => 'OK',
                'document_type' => 'payreq',
            ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);

        $plan->refresh();
        $this->assertSame(1, (int) $plan->status);
    }

    public function test_reject_payreq_without_realization_via_plan_update(): void
    {
        $plan = $this->createAdvanceApprovalPlanWithoutRealization(paymentMethod: 'cash');

        $response = $this->actingAs($this->approver)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->put(route('approvals.plan.update', $plan->id), [
                'status' => 3,
                'remarks' => 'Tidak sesuai',
                'document_type' => 'payreq',
            ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);

        $plan->refresh();
        $this->assertSame(3, (int) $plan->status);
    }

    public function test_datatables_data_does_not_fail_for_advance_without_realization(): void
    {
        $this->createAdvanceApprovalPlanWithoutRealization(paymentMethod: 'cash');

        $response = $this->actingAs($this->approver)->get(
            route('approvals.request.payreqs.data')
        );

        $response->assertOk();
        $response->assertJsonStructure(['data']);
    }

    private function createAdvanceApprovalPlanWithoutRealization(
        string $paymentMethod,
        ?int $transferAccountId = null
    ): ApprovalPlan {
        $payreq = Payreq::query()->create([
            'nomor' => 'PR-ADV-NORLZ-'.uniqid(),
            'user_id' => $this->requestor->id,
            'project' => '000H',
            'department_id' => $this->department->id,
            'amount' => 750000,
            'status' => 'submitted',
            'type' => 'advance',
            'payment_method' => $paymentMethod,
            'transfer_account_id' => $transferAccountId,
            'editable' => '0',
            'deletable' => '0',
            'submit_at' => now(),
            'remarks' => 'Advance tanpa realization',
        ]);

        return ApprovalPlan::query()->create([
            'document_id' => $payreq->id,
            'document_type' => 'payreq',
            'approver_id' => $this->approver->id,
            'status' => 0,
            'is_open' => 1,
        ]);
    }

    private function createReimburseApprovalPlanWithRealization(): ApprovalPlan
    {
        $payreq = Payreq::query()->create([
            'nomor' => 'PR-RMB-'.uniqid(),
            'user_id' => $this->requestor->id,
            'project' => '000H',
            'department_id' => $this->department->id,
            'amount' => 500000,
            'status' => 'submitted',
            'type' => 'reimburse',
            'payment_method' => 'cash',
            'editable' => '0',
            'deletable' => '0',
            'submit_at' => now(),
        ]);

        $realization = Realization::query()->create([
            'nomor' => 'RLZ-WITH-REAL',
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
            'description' => 'Biaya operasional',
            'amount' => 500000,
            'project' => '000H',
            'department_id' => $this->department->id,
        ]);

        return ApprovalPlan::query()->create([
            'document_id' => $payreq->id,
            'document_type' => 'payreq',
            'approver_id' => $this->approver->id,
            'status' => 0,
            'is_open' => 1,
        ]);
    }
}
