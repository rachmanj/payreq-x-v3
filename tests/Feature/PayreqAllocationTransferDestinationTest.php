<?php

namespace Tests\Feature;

use App\Models\Anggaran;
use App\Models\Payreq;
use App\Models\PayreqAnggaranAllocation;
use App\Models\TransferAccount;
use App\Models\User;
use App\Support\PayreqBudgetLinkMode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PayreqAllocationTransferDestinationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private User $otherUser;

    private int $bankId;

    private TransferAccount $accountA;

    private TransferAccount $accountB;

    private TransferAccount $otherUserAccount;

    private Anggaran $anggaranA;

    private Anggaran $anggaranB;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::query()->firstOrCreate(['name' => 'rab_select'], ['guard_name' => 'web']);

        $departmentId = DB::table('departments')->insertGetId([
            'department_name' => 'Test Dept',
            'akronim' => 'TD',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->user = User::factory()->create([
            'project' => '000H',
            'department_id' => $departmentId,
        ]);
        $this->user->givePermissionTo('rab_select');

        $this->otherUser = User::factory()->create([
            'project' => '000H',
            'department_id' => $departmentId,
        ]);

        $this->bankId = DB::table('banks')->insertGetId([
            'name' => 'BCA',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->accountA = TransferAccount::create([
            'user_id' => $this->user->id,
            'bank_id' => $this->bankId,
            'account_number' => '1111111111',
            'account_name' => 'User A',
            'label' => 'Rekening A',
        ]);

        $this->accountB = TransferAccount::create([
            'user_id' => $this->user->id,
            'bank_id' => $this->bankId,
            'account_number' => '2222222222',
            'account_name' => 'User B',
            'label' => 'Rekening B',
        ]);

        $this->otherUserAccount = TransferAccount::create([
            'user_id' => $this->otherUser->id,
            'bank_id' => $this->bankId,
            'account_number' => '3333333333',
            'account_name' => 'Other User',
            'label' => 'Rekening Lain',
        ]);

        $this->anggaranA = $this->makeApprovedAnggaran($this->user, 'RAB-A');
        $this->anggaranB = $this->makeApprovedAnggaran($this->user, 'RAB-B');
    }

    public function test_multi_allocation_draft_with_two_row_accounts_persists_without_header_account(): void
    {
        $response = $this->actingAs($this->user)->post(route('user-payreqs.advance.proses'), $this->multiAllocationPayload([
            'amount' => '1000000',
            'payment_method' => 'cash',
            'allocations' => [
                [
                    'anggaran_id' => $this->anggaranA->id,
                    'amount' => '600000',
                    'transfer_account_id' => $this->accountA->id,
                    'planned_amount' => '600000',
                ],
                [
                    'anggaran_id' => $this->anggaranB->id,
                    'amount' => '400000',
                    'transfer_account_id' => $this->accountB->id,
                    'planned_amount' => '400000',
                ],
            ],
        ]));

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $payreq = Payreq::query()->where('user_id', $this->user->id)->latest('id')->first();
        $this->assertNotNull($payreq);
        $this->assertSame($this->accountA->id, (int) $payreq->transfer_account_id);
        $this->assertSame('transfer', $payreq->payment_method);

        $this->assertDatabaseCount('payreq_anggaran_allocations', 2);
        $this->assertDatabaseHas('payreq_anggaran_allocations', [
            'payreq_id' => $payreq->id,
            'anggaran_id' => $this->anggaranA->id,
            'transfer_account_id' => $this->accountA->id,
            'planned_amount' => 600000,
            'amount' => 600000,
        ]);
        $this->assertDatabaseHas('payreq_anggaran_allocations', [
            'payreq_id' => $payreq->id,
            'anggaran_id' => $this->anggaranB->id,
            'transfer_account_id' => $this->accountB->id,
            'planned_amount' => 400000,
            'amount' => 400000,
        ]);
    }

    public function test_allocation_transfer_account_owned_by_other_user_is_rejected(): void
    {
        $response = $this->actingAs($this->user)->post(route('user-payreqs.advance.proses'), $this->multiAllocationPayload([
            'amount' => '500000',
            'allocations' => [
                [
                    'anggaran_id' => $this->anggaranA->id,
                    'amount' => '500000',
                    'transfer_account_id' => $this->otherUserAccount->id,
                ],
            ],
        ]));

        $response->assertSessionHasErrors('allocations.0.transfer_account_id');
        $this->assertDatabaseCount('payreq_anggaran_allocations', 0);
    }

    public function test_allocation_planned_amount_exceeding_row_amount_is_rejected(): void
    {
        $response = $this->actingAs($this->user)->post(route('user-payreqs.advance.proses'), $this->multiAllocationPayload([
            'amount' => '500000',
            'allocations' => [
                [
                    'anggaran_id' => $this->anggaranA->id,
                    'amount' => '500000',
                    'transfer_account_id' => $this->accountA->id,
                    'planned_amount' => '600000',
                ],
            ],
        ]));

        $response->assertSessionHasErrors('allocations.0.planned_amount');
        $this->assertDatabaseCount('payreq_anggaran_allocations', 0);
    }

    public function test_allocation_planned_amount_less_than_row_amount_is_accepted(): void
    {
        $response = $this->actingAs($this->user)->post(route('user-payreqs.advance.proses'), $this->multiAllocationPayload([
            'amount' => '500000',
            'allocations' => [
                [
                    'anggaran_id' => $this->anggaranA->id,
                    'amount' => '500000',
                    'transfer_account_id' => $this->accountA->id,
                    'planned_amount' => '300000',
                ],
            ],
        ]));

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $payreq = Payreq::query()->where('user_id', $this->user->id)->latest('id')->first();
        $this->assertNotNull($payreq);
        $this->assertDatabaseHas('payreq_anggaran_allocations', [
            'payreq_id' => $payreq->id,
            'anggaran_id' => $this->anggaranA->id,
            'transfer_account_id' => $this->accountA->id,
            'planned_amount' => 300000,
            'amount' => 500000,
        ]);
    }

    public function test_allocation_row_without_transfer_account_is_allowed(): void
    {
        $response = $this->actingAs($this->user)->post(route('user-payreqs.advance.proses'), $this->multiAllocationPayload([
            'amount' => '1000000',
            'payment_method' => 'cash',
            'allocations' => [
                [
                    'anggaran_id' => $this->anggaranA->id,
                    'amount' => '600000',
                    'transfer_account_id' => $this->accountA->id,
                    'planned_amount' => '600000',
                ],
                [
                    'anggaran_id' => $this->anggaranB->id,
                    'amount' => '400000',
                ],
            ],
        ]));

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $payreq = Payreq::query()->where('user_id', $this->user->id)->latest('id')->first();
        $this->assertNotNull($payreq);

        $rows = PayreqAnggaranAllocation::query()->where('payreq_id', $payreq->id)->orderBy('sort_order')->get();
        $this->assertCount(2, $rows);
        $this->assertSame($this->accountA->id, (int) $rows[0]->transfer_account_id);
        $this->assertNull($rows[1]->transfer_account_id);
        $this->assertNull($rows[1]->planned_amount);
    }

    public function test_allocation_planned_amount_with_dot_thousand_separators_is_stored_as_integer(): void
    {
        $response = $this->actingAs($this->user)->post(route('user-payreqs.advance.proses'), $this->multiAllocationPayload([
            'amount' => '3000000',
            'allocations' => [
                [
                    'anggaran_id' => $this->anggaranA->id,
                    'amount' => '3000000',
                    'transfer_account_id' => $this->accountA->id,
                    'planned_amount' => '3.000.000',
                ],
            ],
        ]));

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $payreq = Payreq::query()->where('user_id', $this->user->id)->latest('id')->first();
        $this->assertNotNull($payreq);
        $this->assertDatabaseHas('payreq_anggaran_allocations', [
            'payreq_id' => $payreq->id,
            'anggaran_id' => $this->anggaranA->id,
            'transfer_account_id' => $this->accountA->id,
            'planned_amount' => 3000000,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function multiAllocationPayload(array $overrides = []): array
    {
        return array_merge([
            'button_type' => 'create',
            'employee_id' => $this->user->id,
            'payreq_type' => 'advance',
            'payreq_no' => 'DRAFT-ADV-'.fake()->unique()->numerify('####'),
            'project' => $this->user->project,
            'department_id' => $this->user->department_id,
            'remarks' => 'Advance allocation transfer test',
            'amount' => '1000000',
            'budget_link_mode' => PayreqBudgetLinkMode::MULTI_ALLOCATION,
        ], $overrides);
    }

    private function makeApprovedAnggaran(User $user, string $label): Anggaran
    {
        return Anggaran::query()->create([
            'nomor' => 'TEST-'.$label.'-'.fake()->unique()->numerify('####'),
            'description' => 'Test budget '.$label,
            'project' => $user->project,
            'rab_project' => $user->project,
            'department_id' => $user->department_id,
            'type' => 'event',
            'amount' => 5000000,
            'balance' => 0,
            'usage' => 'user',
            'status' => 'approved',
            'is_active' => 1,
            'created_by' => $user->id,
            'date' => now()->toDateString(),
        ]);
    }
}
