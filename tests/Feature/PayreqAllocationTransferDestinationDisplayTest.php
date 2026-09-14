<?php

namespace Tests\Feature;

use App\Models\Anggaran;
use App\Models\ApprovalPlan;
use App\Models\Department;
use App\Models\Payreq;
use App\Models\PayreqAnggaranAllocation;
use App\Models\Realization;
use App\Models\TransferAccount;
use App\Models\User;
use App\Support\PayreqBudgetLinkMode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PayreqAllocationTransferDestinationDisplayTest extends TestCase
{
    use RefreshDatabase;

    private User $approver;

    private User $requestor;

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

    public function test_approval_payreq_show_displays_allocation_transfer_rows_when_present(): void
    {
        [$plan] = $this->createApprovalAdvanceFixture(withAllocationTransfers: true);

        $response = $this->actingAs($this->approver)->get(
            route('approvals.request.payreqs.show', $plan->id)
        );

        $response->assertOk();
        $response->assertSee('id="allocation-transfer-destinations-readonly"', false);
        $response->assertSee('Rekening Tujuan per Baris Transaksi', false);
        $response->assertSee('Uraian Baris', false);
        $response->assertSee('Baris operasional', false);
        $response->assertSee('Baris logistik', false);
        $response->assertSee($this->accountA->displayLabel, false);
        $response->assertSee($this->accountB->displayLabel, false);
        $response->assertSee('600.000', false);
        $response->assertSee('400.000', false);
    }

    public function test_approval_payreq_show_hides_allocation_transfer_rows_when_empty(): void
    {
        [$plan] = $this->createApprovalAdvanceFixture(withAllocationTransfers: false);

        $response = $this->actingAs($this->approver)->get(
            route('approvals.request.payreqs.show', $plan->id)
        );

        $response->assertOk();
        $response->assertDontSee('id="allocation-transfer-destinations-readonly"', false);
        $response->assertDontSee('Rekening Tujuan per Baris Transaksi', false);
        $response->assertDontSee('Uraian Baris', false);
    }

    public function test_payreq_print_includes_allocation_transfer_rows_when_present(): void
    {
        $payreq = $this->createPrintableAdvancePayreq(withAllocationTransfers: true);

        $response = $this->actingAs($this->requestor)->get(
            route('user-payreqs.print', $payreq->id)
        );

        $response->assertOk();
        $response->assertSee('id="allocation-transfer-destinations-print"', false);
        $response->assertSee('Rekening Tujuan per Baris Transaksi', false);
        $response->assertSee('Baris operasional', false);
        $response->assertSee($this->accountB->displayLabel, false);
        $response->assertSee('400.000', false);
    }

    public function test_payreq_print_without_allocation_transfer_rows_remains_unchanged(): void
    {
        $payreq = $this->createPrintableAdvancePayreq(withAllocationTransfers: false);

        $response = $this->actingAs($this->requestor)->get(
            route('user-payreqs.print', $payreq->id)
        );

        $response->assertOk();
        $response->assertDontSee('id="allocation-transfer-destinations-print"', false);
        $response->assertDontSee('Rekening Tujuan per Baris Transaksi', false);
        $response->assertSee('Payment Request', false);
        $response->assertSee('<b>Transfer</b>', false);
    }

    /**
     * @return array{0: ApprovalPlan}
     */
    private function createApprovalAdvanceFixture(bool $withAllocationTransfers): array
    {
        $anggaranA = $this->makeApprovedAnggaran('RAB-A');
        $anggaranB = $this->makeApprovedAnggaran('RAB-B');

        $payreq = Payreq::query()->create([
            'nomor' => 'PR-APPROVAL-ADV-'.uniqid(),
            'user_id' => $this->requestor->id,
            'project' => '000H',
            'department_id' => $this->department->id,
            'amount' => 1000000,
            'status' => 'submitted',
            'type' => 'advance',
            'payment_method' => 'transfer',
            'transfer_account_id' => $this->accountA->id,
            'budget_link_mode' => PayreqBudgetLinkMode::MULTI_ALLOCATION,
            'rab_id' => $anggaranA->id,
            'editable' => '0',
            'deletable' => '0',
            'submit_at' => now(),
        ]);

        Realization::query()->create([
            'nomor' => 'RLZ-'.uniqid(),
            'payreq_id' => $payreq->id,
            'user_id' => $this->requestor->id,
            'project' => '000H',
            'department_id' => $this->department->id,
            'status' => 'advance-submitted',
            'editable' => '0',
            'deletable' => '0',
            'submit_at' => now(),
        ]);

        PayreqAnggaranAllocation::query()->create([
            'payreq_id' => $payreq->id,
            'anggaran_id' => $anggaranA->id,
            'amount' => 600000,
            'remarks' => 'Baris operasional',
            'transfer_account_id' => $withAllocationTransfers ? $this->accountA->id : null,
            'planned_amount' => $withAllocationTransfers ? 600000 : null,
            'sort_order' => 0,
        ]);

        PayreqAnggaranAllocation::query()->create([
            'payreq_id' => $payreq->id,
            'anggaran_id' => $anggaranB->id,
            'amount' => 400000,
            'remarks' => 'Baris logistik',
            'transfer_account_id' => $withAllocationTransfers ? $this->accountB->id : null,
            'planned_amount' => $withAllocationTransfers ? 400000 : null,
            'sort_order' => 1,
        ]);

        $plan = ApprovalPlan::query()->create([
            'document_id' => $payreq->id,
            'document_type' => 'payreq',
            'approver_id' => $this->approver->id,
            'status' => 0,
            'is_open' => 1,
        ]);

        return [$plan];
    }

    private function createPrintableAdvancePayreq(bool $withAllocationTransfers): Payreq
    {
        $anggaranA = $this->makeApprovedAnggaran('PRINT-A');
        $anggaranB = $this->makeApprovedAnggaran('PRINT-B');

        $payreq = Payreq::query()->create([
            'nomor' => 'PR-PRINT-ADV-'.uniqid(),
            'user_id' => $this->requestor->id,
            'project' => '000H',
            'department_id' => $this->department->id,
            'amount' => 1000000,
            'status' => 'approved',
            'type' => 'advance',
            'payment_method' => 'transfer',
            'transfer_account_id' => $this->accountA->id,
            'budget_link_mode' => PayreqBudgetLinkMode::MULTI_ALLOCATION,
            'rab_id' => $anggaranA->id,
            'remarks' => 'Print test payreq',
            'editable' => '0',
            'deletable' => '0',
            'approved_at' => now(),
        ]);

        PayreqAnggaranAllocation::query()->create([
            'payreq_id' => $payreq->id,
            'anggaran_id' => $anggaranA->id,
            'amount' => 600000,
            'remarks' => 'Baris operasional',
            'transfer_account_id' => $withAllocationTransfers ? $this->accountA->id : null,
            'planned_amount' => $withAllocationTransfers ? 600000 : null,
            'sort_order' => 0,
        ]);

        PayreqAnggaranAllocation::query()->create([
            'payreq_id' => $payreq->id,
            'anggaran_id' => $anggaranB->id,
            'amount' => 400000,
            'remarks' => 'Baris logistik',
            'transfer_account_id' => $withAllocationTransfers ? $this->accountB->id : null,
            'planned_amount' => $withAllocationTransfers ? 400000 : null,
            'sort_order' => 1,
        ]);

        return $payreq;
    }

    private function makeApprovedAnggaran(string $label): Anggaran
    {
        return Anggaran::query()->create([
            'nomor' => 'TEST-'.$label.'-'.fake()->unique()->numerify('####'),
            'description' => 'Test budget '.$label,
            'project' => '000H',
            'rab_project' => '000H',
            'department_id' => $this->department->id,
            'type' => 'event',
            'amount' => 5000000,
            'balance' => 0,
            'usage' => 'user',
            'status' => 'approved',
            'is_active' => 1,
            'created_by' => $this->requestor->id,
            'date' => now()->toDateString(),
        ]);
    }
}
