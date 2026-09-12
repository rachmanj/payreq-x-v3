<?php

namespace Tests\Feature;

use App\Models\Anggaran;
use App\Models\Payreq;
use App\Models\PayreqTransferDestination;
use App\Models\Realization;
use App\Models\TransferAccount;
use App\Models\User;
use App\Support\PayreqBudgetLinkMode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PayreqTransferDestinationsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private User $otherUser;

    private int $bankId;

    private TransferAccount $accountA;

    private TransferAccount $accountB;

    private TransferAccount $otherUserAccount;

    protected function setUp(): void
    {
        parent::setUp();

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
    }

    public function test_advance_with_two_destinations_persists_rows_and_syncs_header_to_first(): void
    {
        $response = $this->actingAs($this->user)->post(route('user-payreqs.advance.proses'), $this->advancePayload([
            'amount' => '1000000',
            'transfer_destinations' => [
                [
                    'transfer_account_id' => $this->accountA->id,
                    'planned_amount' => '600000',
                    'remark' => 'Tujuan utama',
                ],
                [
                    'transfer_account_id' => $this->accountB->id,
                    'planned_amount' => '400000',
                    'remark' => 'Tujuan kedua',
                ],
            ],
        ]));

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $payreq = Payreq::query()->where('user_id', $this->user->id)->latest('id')->first();
        $this->assertNotNull($payreq);
        $this->assertSame($this->accountA->id, (int) $payreq->transfer_account_id);
        $this->assertSame('transfer', $payreq->payment_method);

        $this->assertDatabaseCount('payreq_transfer_destinations', 2);
        $this->assertDatabaseHas('payreq_transfer_destinations', [
            'payreq_id' => $payreq->id,
            'transfer_account_id' => $this->accountA->id,
            'planned_amount' => 600000,
            'remark' => 'Tujuan utama',
            'created_by' => $this->user->id,
        ]);
        $this->assertDatabaseHas('payreq_transfer_destinations', [
            'payreq_id' => $payreq->id,
            'transfer_account_id' => $this->accountB->id,
            'planned_amount' => 400000,
            'remark' => 'Tujuan kedua',
            'created_by' => $this->user->id,
        ]);
    }

    public function test_total_planned_amount_exceeding_payreq_amount_is_rejected(): void
    {
        $response = $this->actingAs($this->user)->post(route('user-payreqs.advance.proses'), $this->advancePayload([
            'amount' => '500000',
            'transfer_destinations' => [
                [
                    'transfer_account_id' => $this->accountA->id,
                    'planned_amount' => '300000',
                ],
                [
                    'transfer_account_id' => $this->accountB->id,
                    'planned_amount' => '300000',
                ],
            ],
        ]));

        $response->assertSessionHasErrors('transfer_destinations');
        $this->assertDatabaseCount('payreq_transfer_destinations', 0);
    }

    public function test_transfer_account_owned_by_other_user_is_rejected(): void
    {
        $response = $this->actingAs($this->user)->post(route('user-payreqs.advance.proses'), $this->advancePayload([
            'amount' => '500000',
            'transfer_destinations' => [
                [
                    'transfer_account_id' => $this->otherUserAccount->id,
                    'planned_amount' => '500000',
                ],
            ],
        ]));

        $response->assertSessionHasErrors('transfer_destinations.0.transfer_account_id');
        $this->assertDatabaseCount('payreq_transfer_destinations', 0);
    }

    public function test_without_destination_list_preserves_legacy_single_account_behavior(): void
    {
        $response = $this->actingAs($this->user)->post(route('user-payreqs.advance.proses'), $this->advancePayload([
            'amount' => '750000',
            'payment_method' => 'transfer',
            'transfer_account_id' => $this->accountB->id,
        ]));

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $payreq = Payreq::query()->where('user_id', $this->user->id)->latest('id')->first();
        $this->assertNotNull($payreq);
        $this->assertSame($this->accountB->id, (int) $payreq->transfer_account_id);
        $this->assertSame('transfer', $payreq->payment_method);
        $this->assertDatabaseCount('payreq_transfer_destinations', 0);
    }

    public function test_advance_update_replaces_destination_list_without_duplicate_rows(): void
    {
        $createResponse = $this->actingAs($this->user)->post(route('user-payreqs.advance.proses'), $this->advancePayload([
            'amount' => '1000000',
            'transfer_destinations' => [
                [
                    'transfer_account_id' => $this->accountA->id,
                    'planned_amount' => '600000',
                ],
                [
                    'transfer_account_id' => $this->accountB->id,
                    'planned_amount' => '400000',
                ],
            ],
        ]));

        $createResponse->assertSessionHasNoErrors();
        $payreq = Payreq::query()->where('user_id', $this->user->id)->latest('id')->firstOrFail();

        $updateResponse = $this->actingAs($this->user)->post(route('user-payreqs.advance.proses'), $this->advancePayload([
            'button_type' => 'edit',
            'payreq_id' => $payreq->id,
            'amount' => '1000000',
            'transfer_destinations' => [
                [
                    'transfer_account_id' => $this->accountB->id,
                    'planned_amount' => '700000',
                    'remark' => 'Updated B',
                ],
                [
                    'transfer_account_id' => $this->accountA->id,
                    'planned_amount' => '300000',
                    'remark' => 'Updated A',
                ],
            ],
        ]));

        $updateResponse->assertSessionHasNoErrors();

        $payreq->refresh();
        $this->assertSame($this->accountB->id, (int) $payreq->transfer_account_id);
        $this->assertSame(2, PayreqTransferDestination::query()->where('payreq_id', $payreq->id)->count());

        $this->assertDatabaseMissing('payreq_transfer_destinations', [
            'payreq_id' => $payreq->id,
            'transfer_account_id' => $this->accountA->id,
            'planned_amount' => 600000,
        ]);
        $this->assertDatabaseHas('payreq_transfer_destinations', [
            'payreq_id' => $payreq->id,
            'transfer_account_id' => $this->accountA->id,
            'planned_amount' => 300000,
            'remark' => 'Updated A',
        ]);
        $this->assertDatabaseHas('payreq_transfer_destinations', [
            'payreq_id' => $payreq->id,
            'transfer_account_id' => $this->accountB->id,
            'planned_amount' => 700000,
            'remark' => 'Updated B',
        ]);
    }

    public function test_planned_amount_with_dot_thousand_separators_is_stored_as_integer(): void
    {
        $response = $this->actingAs($this->user)->post(route('user-payreqs.advance.proses'), $this->advancePayload([
            'amount' => '3000000',
            'transfer_destinations' => [
                [
                    'transfer_account_id' => $this->accountA->id,
                    'planned_amount' => '3.000.000',
                ],
            ],
        ]));

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $payreq = Payreq::query()->where('user_id', $this->user->id)->latest('id')->first();
        $this->assertNotNull($payreq);
        $this->assertDatabaseHas('payreq_transfer_destinations', [
            'payreq_id' => $payreq->id,
            'transfer_account_id' => $this->accountA->id,
            'planned_amount' => 3000000,
        ]);
    }

    public function test_present_flag_with_empty_list_deletes_all_destinations(): void
    {
        $createResponse = $this->actingAs($this->user)->post(route('user-payreqs.advance.proses'), $this->advancePayload([
            'amount' => '1000000',
            'transfer_destinations' => [
                [
                    'transfer_account_id' => $this->accountA->id,
                    'planned_amount' => '600000',
                ],
                [
                    'transfer_account_id' => $this->accountB->id,
                    'planned_amount' => '400000',
                ],
            ],
        ]));

        $createResponse->assertSessionHasNoErrors();
        $payreq = Payreq::query()->where('user_id', $this->user->id)->latest('id')->firstOrFail();
        $this->assertSame(2, PayreqTransferDestination::query()->where('payreq_id', $payreq->id)->count());

        $updateResponse = $this->actingAs($this->user)->post(route('user-payreqs.advance.proses'), $this->advancePayload([
            'button_type' => 'edit',
            'payreq_id' => $payreq->id,
            'amount' => '1000000',
            'payment_method' => 'transfer',
            'transfer_account_id' => $this->accountB->id,
            'transfer_destinations_present' => '1',
            'transfer_destinations' => [],
        ]));

        $updateResponse->assertSessionHasNoErrors();

        $payreq->refresh();
        $this->assertSame($this->accountB->id, (int) $payreq->transfer_account_id);
        $this->assertSame(0, PayreqTransferDestination::query()->where('payreq_id', $payreq->id)->count());
    }

    public function test_without_present_flag_empty_list_preserves_existing_destinations(): void
    {
        $createResponse = $this->actingAs($this->user)->post(route('user-payreqs.advance.proses'), $this->advancePayload([
            'amount' => '1000000',
            'transfer_destinations' => [
                [
                    'transfer_account_id' => $this->accountA->id,
                    'planned_amount' => '600000',
                ],
                [
                    'transfer_account_id' => $this->accountB->id,
                    'planned_amount' => '400000',
                ],
            ],
        ]));

        $createResponse->assertSessionHasNoErrors();
        $payreq = Payreq::query()->where('user_id', $this->user->id)->latest('id')->firstOrFail();
        $this->assertSame(2, PayreqTransferDestination::query()->where('payreq_id', $payreq->id)->count());

        $updateResponse = $this->actingAs($this->user)->post(route('user-payreqs.advance.proses'), $this->advancePayload([
            'button_type' => 'edit',
            'payreq_id' => $payreq->id,
            'amount' => '1000000',
            'payment_method' => 'transfer',
            'transfer_account_id' => $this->accountB->id,
            'transfer_destinations' => [],
        ]));

        $updateResponse->assertSessionHasNoErrors();

        $payreq->refresh();
        $this->assertSame($this->accountB->id, (int) $payreq->transfer_account_id);
        $this->assertSame(2, PayreqTransferDestination::query()->where('payreq_id', $payreq->id)->count());
    }

    public function test_reimburse_update_rab_syncs_destinations_and_header(): void
    {
        $anggaran = $this->makeApprovedAnggaran($this->user);
        $payreq = Payreq::query()->create([
            'user_id' => $this->user->id,
            'nomor' => 'REIMB-001',
            'type' => 'reimburse',
            'status' => 'draft',
            'amount' => 900000,
            'project' => $this->user->project,
            'department_id' => $this->user->department_id,
            'rab_id' => $anggaran->id,
            'payment_method' => 'cash',
            'transfer_account_id' => null,
        ]);

        Realization::query()->create([
            'payreq_id' => $payreq->id,
            'project' => $payreq->project,
            'department_id' => $payreq->department_id,
            'remarks' => 'Reimburse test',
            'user_id' => $payreq->user_id,
            'nomor' => 'REAL-001',
            'status' => 'reimburse-draft',
        ]);

        $response = $this->actingAs($this->user)->postJson(route('user-payreqs.reimburse.update_rab'), [
            'payreq_id' => $payreq->id,
            'rab_id' => $anggaran->id,
            'payment_method' => 'transfer',
            'transfer_destinations' => [
                [
                    'transfer_account_id' => $this->accountA->id,
                    'planned_amount' => '500000',
                ],
                [
                    'transfer_account_id' => $this->accountB->id,
                    'planned_amount' => '400000',
                ],
            ],
        ]);

        $response->assertOk();

        $payreq->refresh();
        $this->assertSame($this->accountA->id, (int) $payreq->transfer_account_id);
        $this->assertSame(2, PayreqTransferDestination::query()->where('payreq_id', $payreq->id)->count());
    }

    public function test_advance_edit_with_transfer_shows_transfer_destinations_form_data(): void
    {
        $payreq = Payreq::query()->create([
            'user_id' => $this->user->id,
            'nomor' => 'ADV-UI-TR',
            'type' => 'advance',
            'status' => 'draft',
            'amount' => 1000000,
            'project' => $this->user->project,
            'department_id' => $this->user->department_id,
            'payment_method' => 'transfer',
            'transfer_account_id' => $this->accountA->id,
            'budget_link_mode' => PayreqBudgetLinkMode::LEGACY,
            'remarks' => 'UI test transfer',
        ]);

        PayreqTransferDestination::query()->create([
            'payreq_id' => $payreq->id,
            'transfer_account_id' => $this->accountA->id,
            'planned_amount' => 600000,
            'remark' => 'Tujuan A',
            'created_by' => $this->user->id,
        ]);

        PayreqTransferDestination::query()->create([
            'payreq_id' => $payreq->id,
            'transfer_account_id' => $this->accountB->id,
            'planned_amount' => 400000,
            'remark' => 'Tujuan B',
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->get(route('user-payreqs.advance.edit', $payreq->id));

        $response->assertOk();
        $response->assertSee('name="transfer_destinations_present"', false);
        $response->assertSee('Daftar Tujuan Transfer', false);
        $response->assertSee('name="transfer_destinations[0][transfer_account_id]"', false);
        $response->assertSee('name="transfer_destinations[1][transfer_account_id]"', false);
        $response->assertSee('value="600000"', false);
        $response->assertSee('value="400000"', false);
        $response->assertSee('Tujuan A', false);
        $response->assertSee('Tujuan B', false);
        $response->assertDontSee('id="transfer-destinations-block" style="display:none;"', false);
    }

    public function test_advance_edit_with_cash_hides_transfer_destinations_block(): void
    {
        $payreq = Payreq::query()->create([
            'user_id' => $this->user->id,
            'nomor' => 'ADV-UI-CASH',
            'type' => 'advance',
            'status' => 'draft',
            'amount' => 1000000,
            'project' => $this->user->project,
            'department_id' => $this->user->department_id,
            'payment_method' => 'cash',
            'transfer_account_id' => null,
            'budget_link_mode' => PayreqBudgetLinkMode::LEGACY,
            'remarks' => 'UI test cash',
        ]);

        $response = $this->actingAs($this->user)->get(route('user-payreqs.advance.edit', $payreq->id));

        $response->assertOk();
        $response->assertSee('id="transfer-destinations-block" style="display:none;"', false);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function advancePayload(array $overrides = []): array
    {
        return array_merge([
            'button_type' => 'create',
            'employee_id' => $this->user->id,
            'payreq_type' => 'advance',
            'payreq_no' => 'DRAFT-ADV-'.fake()->unique()->numerify('####'),
            'project' => $this->user->project,
            'department_id' => $this->user->department_id,
            'remarks' => 'Advance transfer destinations test',
            'amount' => '1000000',
            'budget_link_mode' => PayreqBudgetLinkMode::LEGACY,
            'payment_method' => 'transfer',
            'transfer_account_id' => $this->accountA->id,
        ], $overrides);
    }

    private function makeApprovedAnggaran(User $user): Anggaran
    {
        return Anggaran::query()->create([
            'nomor' => 'TEST-RAB-'.fake()->unique()->numerify('####'),
            'description' => 'Test budget',
            'project' => $user->project,
            'rab_project' => $user->project,
            'department_id' => $user->department_id,
            'type' => 'event',
            'amount' => 1000000,
            'balance' => 0,
            'usage' => 'user',
            'status' => 'approved',
            'is_active' => 1,
            'created_by' => $user->id,
            'date' => now()->toDateString(),
        ]);
    }
}
