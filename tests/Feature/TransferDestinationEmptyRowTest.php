<?php

namespace Tests\Feature;

use App\Models\Payreq;
use App\Models\PayreqTransferDestination;
use App\Models\TransferAccount;
use App\Models\User;
use App\Support\PayreqBudgetLinkMode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TransferDestinationEmptyRowTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private TransferAccount $account;

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
            'project' => '022C',
            'department_id' => $departmentId,
        ]);

        $bankId = DB::table('banks')->insertGetId([
            'name' => 'BCA',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->account = TransferAccount::create([
            'user_id' => $this->user->id,
            'bank_id' => $bankId,
            'account_number' => '9999888877',
            'account_name' => 'Vendor X',
            'label' => 'Vendor X',
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function formPayloadWithEmptyDestinationRow(array $overrides = []): array
    {
        return array_merge([
            'transfer_destinations_present' => '1',
            'transfer_destinations' => [
                ['transfer_account_id' => '', 'planned_amount' => '', 'remark' => ''],
            ],
        ], $overrides);
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
            'payreq_no' => 'DRAFT-EMPTY-'.fake()->unique()->numerify('#####'),
            'project' => '022C',
            'department_id' => $this->user->department_id,
            'remarks' => 'Advance with empty destination row',
            'amount' => '1500000',
            'budget_link_mode' => PayreqBudgetLinkMode::LEGACY,
            'payment_method' => 'cash',
        ], $this->formPayloadWithEmptyDestinationRow(), $overrides);
    }

    public function test_advance_cash_saves_when_form_sends_one_empty_destination_row(): void
    {
        $response = $this->actingAs($this->user)->post(
            route('user-payreqs.advance.proses'),
            $this->advancePayload()
        );

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $payreq = Payreq::query()->where('user_id', $this->user->id)->latest('id')->first();
        $this->assertNotNull($payreq);
        $this->assertSame('cash', $payreq->payment_method);
        $this->assertDatabaseCount('payreq_transfer_destinations', 0);
    }

    public function test_advance_transfer_with_header_account_saves_when_form_sends_one_empty_destination_row(): void
    {
        $response = $this->actingAs($this->user)->post(
            route('user-payreqs.advance.proses'),
            $this->advancePayload([
                'payment_method' => 'transfer',
                'transfer_account_id' => $this->account->id,
            ])
        );

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $payreq = Payreq::query()->where('user_id', $this->user->id)->latest('id')->first();
        $this->assertNotNull($payreq);
        $this->assertSame('transfer', $payreq->payment_method);
        $this->assertSame($this->account->id, (int) $payreq->transfer_account_id);
        $this->assertDatabaseCount('payreq_transfer_destinations', 0);
    }

    public function test_advance_transfer_without_header_account_still_errors(): void
    {
        $response = $this->actingAs($this->user)->post(
            route('user-payreqs.advance.proses'),
            $this->advancePayload([
                'payment_method' => 'transfer',
            ])
        );

        $response->assertSessionHasErrors('transfer_account_id');
        $this->assertDatabaseCount('payreqs', 0);
    }

    public function test_filled_destination_row_still_persists_normally(): void
    {
        $response = $this->actingAs($this->user)->post(
            route('user-payreqs.advance.proses'),
            $this->advancePayload([
                'payment_method' => 'transfer',
                'transfer_account_id' => $this->account->id,
                'transfer_destinations' => [
                    [
                        'transfer_account_id' => $this->account->id,
                        'planned_amount' => '1500000',
                        'remark' => 'Tujuan utama',
                    ],
                ],
            ])
        );

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $payreq = Payreq::query()->where('user_id', $this->user->id)->latest('id')->first();
        $this->assertNotNull($payreq);
        $this->assertSame('transfer', $payreq->payment_method);
        $this->assertSame($this->account->id, (int) $payreq->transfer_account_id);
        $this->assertDatabaseHas('payreq_transfer_destinations', [
            'payreq_id' => $payreq->id,
            'transfer_account_id' => $this->account->id,
            'planned_amount' => 1500000,
            'remark' => 'Tujuan utama',
        ]);
    }

    public function test_reimburse_saves_when_form_sends_one_empty_destination_row(): void
    {
        $response = $this->actingAs($this->user)->post(
            route('user-payreqs.reimburse.store'),
            array_merge([
                'employee_id' => $this->user->id,
                'payreq_type' => 'reimburse',
                'payreq_no' => 'DRAFT-REIMB-'.fake()->unique()->numerify('#####'),
                'project' => '022C',
                'department_id' => $this->user->department_id,
                'remarks' => 'Reimburse with empty destination row',
                'payment_method' => 'cash',
            ], $this->formPayloadWithEmptyDestinationRow())
        );

        $response->assertOk();
        $response->assertSessionHasNoErrors();

        $payreq = Payreq::query()->where('user_id', $this->user->id)->latest('id')->first();
        $this->assertNotNull($payreq);
        $this->assertSame('reimburse', $payreq->type);
        $this->assertSame('cash', $payreq->payment_method);
        $this->assertSame(0, PayreqTransferDestination::query()->where('payreq_id', $payreq->id)->count());
    }
}
