<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Anggaran;
use App\Models\Outgoing;
use App\Models\Payreq;
use App\Models\PayreqAnggaranAllocation;
use App\Models\TransferAccount;
use App\Models\User;
use App\Support\PayreqBudgetLinkMode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CashierMultiDestinationTransferTest extends TestCase
{
    use RefreshDatabase;

    private User $cashier;

    private User $requestor;

    private User $otherUser;

    private int $bankId;

    private TransferAccount $requestorAccountA;

    private TransferAccount $requestorAccountB;

    private TransferAccount $otherUserAccount;

    private Account $bankAccount;

    private int $departmentId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->departmentId = DB::table('departments')->insertGetId([
            'department_name' => 'Test Dept',
            'akronim' => 'TD',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->cashier = User::factory()->create([
            'project' => '000H',
            'department_id' => $this->departmentId,
        ]);
        $this->requestor = User::factory()->create([
            'project' => '000H',
            'department_id' => $this->departmentId,
        ]);
        $this->otherUser = User::factory()->create([
            'project' => '000H',
            'department_id' => $this->departmentId,
        ]);

        $this->bankId = DB::table('banks')->insertGetId([
            'name' => 'BCA',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->requestorAccountA = TransferAccount::create([
            'user_id' => $this->requestor->id,
            'bank_id' => $this->bankId,
            'account_number' => '1111111111',
            'account_name' => 'Requestor A',
            'label' => 'Rekening A',
        ]);

        $this->requestorAccountB = TransferAccount::create([
            'user_id' => $this->requestor->id,
            'bank_id' => $this->bankId,
            'account_number' => '2222222222',
            'account_name' => 'Requestor B',
            'label' => 'Rekening B',
        ]);

        $this->otherUserAccount = TransferAccount::create([
            'user_id' => $this->otherUser->id,
            'bank_id' => $this->bankId,
            'account_number' => '3333333333',
            'account_name' => 'Other User',
            'label' => 'Rekening Lain',
        ]);

        $this->bankAccount = Account::create([
            'type' => 'bank',
            'account_number' => '11020101',
            'account_name' => 'Bank Account',
            'project' => '000H',
            'app_balance' => 100000000,
            'is_active' => true,
        ]);

        Account::create([
            'type' => 'cash',
            'account_number' => '11010101',
            'account_name' => 'Cash Account',
            'project' => '000H',
            'app_balance' => 100000000,
            'is_active' => true,
        ]);

        Account::create([
            'type' => 'advance',
            'account_number' => '11030101',
            'account_name' => 'Advance Account',
            'project' => '000H',
            'app_balance' => 0,
            'is_active' => true,
        ]);
    }

    private function createTransferPayreq(int $amount = 1000000): Payreq
    {
        return Payreq::create([
            'user_id' => $this->requestor->id,
            'nomor' => 'PR-TRANSFER-001',
            'type' => 'advance',
            'amount' => $amount,
            'remarks' => 'Transfer payreq test',
            'project' => '000H',
            'status' => 'approved',
            'payment_method' => 'transfer',
            'transfer_account_id' => $this->requestorAccountA->id,
            'editable' => 0,
            'deletable' => 0,
        ]);
    }

    private function storePayPayload(int $amount, ?int $transferAccountId = null): array
    {
        $payload = [
            'amount' => $amount,
            'date' => now()->toDateString(),
            'account_id' => $this->bankAccount->id,
        ];

        if ($transferAccountId !== null) {
            $payload['transfer_account_id'] = $transferAccountId;
        }

        return $payload;
    }

    public function test_split_payments_can_use_different_destination_accounts(): void
    {
        $payreq = $this->createTransferPayreq();

        $this->actingAs($this->cashier)
            ->put(route('cashier.approveds.store_pay', $payreq->id), $this->storePayPayload(400000, $this->requestorAccountA->id))
            ->assertRedirect(route('cashier.approveds.pay', $payreq->id));

        $this->actingAs($this->cashier)
            ->put(route('cashier.approveds.store_pay', $payreq->id), $this->storePayPayload(600000, $this->requestorAccountB->id))
            ->assertRedirect(route('cashier.approveds.pay', $payreq->id));

        $outgoings = Outgoing::where('payreq_id', $payreq->id)->orderBy('id')->get();

        $this->assertCount(2, $outgoings);
        $this->assertSame($this->requestorAccountA->id, (int) $outgoings[0]->transfer_account_id);
        $this->assertSame($this->requestorAccountB->id, (int) $outgoings[1]->transfer_account_id);
    }

    public function test_transfer_account_owned_by_other_user_is_rejected(): void
    {
        $payreq = $this->createTransferPayreq();

        $this->actingAs($this->cashier)
            ->put(route('cashier.approveds.store_pay', $payreq->id), $this->storePayPayload(500000, $this->otherUserAccount->id))
            ->assertRedirect(route('cashier.approveds.pay', $payreq->id))
            ->assertSessionHas('error', 'Rekening tujuan tidak valid untuk pembayaran ini.');

        $this->assertSame(0, Outgoing::where('payreq_id', $payreq->id)->count());
    }

    public function test_empty_transfer_account_id_falls_back_to_payreq_header_account(): void
    {
        $payreq = $this->createTransferPayreq();

        $this->actingAs($this->cashier)
            ->put(route('cashier.approveds.store_pay', $payreq->id), $this->storePayPayload(500000))
            ->assertRedirect(route('cashier.approveds.pay', $payreq->id));

        $outgoing = Outgoing::where('payreq_id', $payreq->id)->first();

        $this->assertNotNull($outgoing);
        $this->assertSame($this->requestorAccountA->id, (int) $outgoing->transfer_account_id);
    }

    public function test_payment_exceeding_payreq_amount_is_rejected(): void
    {
        $payreq = $this->createTransferPayreq(1000000);

        $this->actingAs($this->cashier)
            ->put(route('cashier.approveds.store_pay', $payreq->id), $this->storePayPayload(1000001, $this->requestorAccountA->id))
            ->assertRedirect(route('cashier.approveds.pay', $payreq->id))
            ->assertSessionHas('error', 'Pembayaran tidak boleh melebihi jumlah yang tersisa!');

        $this->assertSame(0, Outgoing::where('payreq_id', $payreq->id)->count());
    }

    public function test_split_page_shows_allocation_transfer_plan_when_present(): void
    {
        $payreq = $this->createTransferPayreq();
        $payreq->update([
            'budget_link_mode' => PayreqBudgetLinkMode::MULTI_ALLOCATION,
        ]);

        $anggaranA = $this->makeApprovedAnggaran('RAB-A');
        $anggaranB = $this->makeApprovedAnggaran('RAB-B');

        PayreqAnggaranAllocation::query()->create([
            'payreq_id' => $payreq->id,
            'anggaran_id' => $anggaranA->id,
            'amount' => 600000,
            'remarks' => 'Baris operasional',
            'transfer_account_id' => $this->requestorAccountA->id,
            'planned_amount' => 600000,
            'sort_order' => 0,
        ]);

        PayreqAnggaranAllocation::query()->create([
            'payreq_id' => $payreq->id,
            'anggaran_id' => $anggaranB->id,
            'amount' => 400000,
            'remarks' => 'Baris logistik',
            'transfer_account_id' => $this->requestorAccountB->id,
            'planned_amount' => 400000,
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($this->cashier)->get(
            route('cashier.approveds.pay', $payreq->id)
        );

        $response->assertOk();
        $response->assertSee('id="allocation-transfer-plan-readonly"', false);
        $response->assertSee('Rencana Transfer per Baris Transaksi', false);
        $response->assertSee('Baris operasional', false);
        $response->assertSee('Baris logistik', false);
        $response->assertSee($this->requestorAccountA->displayLabel, false);
        $response->assertSee($this->requestorAccountB->displayLabel, false);
        $response->assertSee('600.000', false);
        $response->assertSee('400.000', false);
        $response->assertSee('id="btn-fill-from-plan"', false);
        $response->assertSee('transferPlan', false);
    }

    public function test_split_page_hides_allocation_transfer_plan_when_empty(): void
    {
        $payreq = $this->createTransferPayreq();
        $payreq->update([
            'budget_link_mode' => PayreqBudgetLinkMode::MULTI_ALLOCATION,
        ]);

        $anggaranA = $this->makeApprovedAnggaran('RAB-A');

        PayreqAnggaranAllocation::query()->create([
            'payreq_id' => $payreq->id,
            'anggaran_id' => $anggaranA->id,
            'amount' => 1000000,
            'remarks' => 'Tanpa rekening tujuan',
            'sort_order' => 0,
        ]);

        $response = $this->actingAs($this->cashier)->get(
            route('cashier.approveds.pay', $payreq->id)
        );

        $response->assertOk();
        $response->assertDontSee('id="allocation-transfer-plan-readonly"', false);
        $response->assertDontSee('Rencana Transfer per Baris Transaksi', false);
        $response->assertDontSee('id="btn-fill-from-plan"', false);
        $response->assertDontSee('transferPlan', false);
    }

    private function makeApprovedAnggaran(string $label): Anggaran
    {
        return Anggaran::query()->create([
            'nomor' => 'TEST-'.$label.'-'.fake()->unique()->numerify('####'),
            'description' => 'Test budget '.$label,
            'project' => '000H',
            'rab_project' => '000H',
            'department_id' => $this->departmentId,
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
