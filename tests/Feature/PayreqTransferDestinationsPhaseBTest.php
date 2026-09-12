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

class PayreqTransferDestinationsPhaseBTest extends TestCase
{
    use RefreshDatabase;

    private User $approver;

    private User $requestor;

    private User $cashier;

    private Department $department;

    private int $bankId;

    private TransferAccount $accountA;

    private TransferAccount $accountB;

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

        $this->cashier = User::factory()->create([
            'project' => '000H',
            'department_id' => $this->department->id,
        ]);

        $this->bankId = DB::table('banks')->insertGetId([
            'name' => 'BCA',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->accountA = TransferAccount::create([
            'user_id' => $this->requestor->id,
            'bank_id' => $this->bankId,
            'account_number' => '1111111111',
            'account_name' => 'Requestor A',
            'label' => 'Rekening A',
        ]);

        $this->accountB = TransferAccount::create([
            'user_id' => $this->requestor->id,
            'bank_id' => $this->bankId,
            'account_number' => '2222222222',
            'account_name' => 'Requestor B',
            'label' => 'Rekening B',
        ]);
    }

    public function test_approval_payreq_show_displays_transfer_destinations_when_present(): void
    {
        [$plan, $payreq] = $this->createApprovalPayreqFixture(withDestinations: true);

        $response = $this->actingAs($this->approver)->get(
            route('approvals.request.payreqs.show', $plan->id)
        );

        $response->assertOk();
        $response->assertSee('id="transfer-destinations-readonly"', false);
        $response->assertSee('Daftar Tujuan Transfer', false);
        $response->assertSee($this->accountA->displayLabel, false);
        $response->assertSee('600.000', false);
        $response->assertSee('Tujuan utama', false);
    }

    public function test_approval_payreq_show_hides_transfer_destinations_when_empty(): void
    {
        [$plan] = $this->createApprovalPayreqFixture(withDestinations: false);

        $response = $this->actingAs($this->approver)->get(
            route('approvals.request.payreqs.show', $plan->id)
        );

        $response->assertOk();
        $response->assertDontSee('id="transfer-destinations-readonly"', false);
        $response->assertDontSee('Daftar Tujuan Transfer', false);
    }

    public function test_payreq_print_includes_transfer_destinations_block_when_present(): void
    {
        $payreq = $this->createPrintableAdvancePayreq(withDestinations: true);

        $response = $this->actingAs($this->requestor)->get(
            route('user-payreqs.print', $payreq->id)
        );

        $response->assertOk();
        $response->assertSee('id="transfer-destinations-print"', false);
        $response->assertSee('Daftar Tujuan Transfer', false);
        $response->assertSee($this->accountB->displayLabel, false);
        $response->assertSee('400.000', false);
    }

    public function test_payreq_print_without_destinations_remains_unchanged(): void
    {
        $payreq = $this->createPrintableAdvancePayreq(withDestinations: false);

        $response = $this->actingAs($this->requestor)->get(
            route('user-payreqs.print', $payreq->id)
        );

        $response->assertOk();
        $response->assertDontSee('id="transfer-destinations-print"', false);
        $response->assertDontSee('Daftar Tujuan Transfer', false);
        $response->assertSee('Payment Request', false);
        $response->assertSee('<b>Transfer</b>', false);
    }

    public function test_cashier_split_shows_fill_from_plan_button_when_destinations_exist(): void
    {
        $payreq = $this->createApprovedTransferPayreq(withDestinations: true);

        $response = $this->actingAs($this->cashier)->get(
            route('cashier.approveds.pay', $payreq->id)
        );

        $response->assertOk();
        $response->assertSee('id="btn-fill-from-plan"', false);
        $response->assertSee('Isi dari rencana', false);
        $response->assertSee('transferPlan', false);
    }

    public function test_cashier_split_hides_fill_from_plan_button_when_destinations_empty(): void
    {
        $payreq = $this->createApprovedTransferPayreq(withDestinations: false);

        $response = $this->actingAs($this->cashier)->get(
            route('cashier.approveds.pay', $payreq->id)
        );

        $response->assertOk();
        $response->assertDontSee('id="btn-fill-from-plan"', false);
        $response->assertDontSee('Isi dari rencana', false);
        $response->assertDontSee('transferPlan', false);
    }

    /**
     * @return array{0: ApprovalPlan, 1: Payreq}
     */
    private function createApprovalPayreqFixture(bool $withDestinations): array
    {
        $payreq = Payreq::query()->create([
            'nomor' => 'PR-APPROVAL-'.uniqid(),
            'user_id' => $this->requestor->id,
            'project' => '000H',
            'department_id' => $this->department->id,
            'amount' => 1000000,
            'status' => 'submitted',
            'type' => 'reimburse',
            'payment_method' => 'transfer',
            'transfer_account_id' => $this->accountA->id,
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
            'description' => 'Biaya operasional',
            'amount' => 1000000,
            'project' => '000H',
            'department_id' => $this->department->id,
        ]);

        if ($withDestinations) {
            PayreqTransferDestination::query()->create([
                'payreq_id' => $payreq->id,
                'transfer_account_id' => $this->accountA->id,
                'planned_amount' => 600000,
                'remark' => 'Tujuan utama',
                'created_by' => $this->requestor->id,
            ]);

            PayreqTransferDestination::query()->create([
                'payreq_id' => $payreq->id,
                'transfer_account_id' => $this->accountB->id,
                'planned_amount' => 400000,
                'remark' => 'Tujuan kedua',
                'created_by' => $this->requestor->id,
            ]);
        }

        $plan = ApprovalPlan::query()->create([
            'document_id' => $payreq->id,
            'document_type' => 'payreq',
            'approver_id' => $this->approver->id,
            'status' => 0,
            'is_open' => 1,
        ]);

        return [$plan, $payreq];
    }

    private function createPrintableAdvancePayreq(bool $withDestinations): Payreq
    {
        $payreq = Payreq::query()->create([
            'nomor' => 'PR-PRINT-'.uniqid(),
            'user_id' => $this->requestor->id,
            'project' => '000H',
            'department_id' => $this->department->id,
            'amount' => 1000000,
            'status' => 'approved',
            'type' => 'advance',
            'payment_method' => 'transfer',
            'transfer_account_id' => $this->accountA->id,
            'remarks' => 'Print test payreq',
            'editable' => '0',
            'deletable' => '0',
            'approved_at' => now(),
        ]);

        if ($withDestinations) {
            PayreqTransferDestination::query()->create([
                'payreq_id' => $payreq->id,
                'transfer_account_id' => $this->accountA->id,
                'planned_amount' => 600000,
                'remark' => 'Rencana A',
                'created_by' => $this->requestor->id,
            ]);

            PayreqTransferDestination::query()->create([
                'payreq_id' => $payreq->id,
                'transfer_account_id' => $this->accountB->id,
                'planned_amount' => 400000,
                'remark' => 'Rencana B',
                'created_by' => $this->requestor->id,
            ]);
        }

        return $payreq;
    }

    private function createApprovedTransferPayreq(bool $withDestinations): Payreq
    {
        $payreq = Payreq::query()->create([
            'nomor' => 'PR-CASHIER-'.uniqid(),
            'user_id' => $this->requestor->id,
            'project' => '000H',
            'department_id' => $this->department->id,
            'amount' => 1000000,
            'status' => 'approved',
            'type' => 'advance',
            'payment_method' => 'transfer',
            'transfer_account_id' => $this->accountA->id,
            'remarks' => 'Cashier split test',
            'editable' => '0',
            'deletable' => '0',
            'approved_at' => now(),
        ]);

        if ($withDestinations) {
            PayreqTransferDestination::query()->create([
                'payreq_id' => $payreq->id,
                'transfer_account_id' => $this->accountA->id,
                'planned_amount' => 600000,
                'remark' => 'Rencana kasir A',
                'created_by' => $this->requestor->id,
            ]);

            PayreqTransferDestination::query()->create([
                'payreq_id' => $payreq->id,
                'transfer_account_id' => $this->accountB->id,
                'planned_amount' => null,
                'remark' => 'Rencana kasir B',
                'created_by' => $this->requestor->id,
            ]);
        }

        return $payreq;
    }
}
