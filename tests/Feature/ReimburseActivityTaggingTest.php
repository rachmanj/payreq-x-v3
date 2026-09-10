<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Activity;
use App\Models\Department;
use App\Models\Payreq;
use App\Models\Realization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReimburseActivityTaggingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Department $department;

    protected Realization $realization;

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

        $this->user = User::factory()->create([
            'project' => '000H',
            'department_id' => $this->department->id,
        ]);

        $payreq = Payreq::query()->create([
            'nomor' => 'PR-RMB-'.uniqid(),
            'user_id' => $this->user->id,
            'project' => '000H',
            'department_id' => $this->department->id,
            'amount' => 0,
            'status' => 'draft',
            'type' => 'reimburse',
            'editable' => '1',
            'deletable' => '1',
        ]);

        $this->realization = Realization::query()->create([
            'nomor' => 'RLZ-RMB-'.uniqid(),
            'payreq_id' => $payreq->id,
            'user_id' => $this->user->id,
            'project' => '000H',
            'department_id' => $this->department->id,
            'status' => 'reimburse-draft',
            'editable' => '1',
            'deletable' => '1',
        ]);
    }

    protected function createReklasifikasiActivity(): Activity
    {
        $account = Account::query()->create([
            'account_number' => '51112001',
            'account_name' => 'Mobilisation',
            'type' => 'expense',
            'project' => '000H',
            'is_active' => true,
            'is_hidden' => false,
        ]);

        return Activity::query()->create([
            'code' => 'KEG-2026-001',
            'name' => 'Mobilisasi Project X',
            'periode' => '2026-09',
            'mode' => 'reklasifikasi',
            'status' => 'open',
            'account_id' => $account->id,
            'project' => '000H',
            'created_by' => $this->user->id,
        ]);
    }

    protected function storeDetailPayload(array $overrides = []): array
    {
        return array_merge([
            'realization_id' => $this->realization->id,
            'description' => 'Biaya transport',
            'amount' => '150000',
            'expense_date' => now()->format('Y-m-d'),
        ], $overrides);
    }

    public function test_store_detail_with_reklasifikasi_activity_id_is_persisted(): void
    {
        $activity = $this->createReklasifikasiActivity();

        $this->actingAs($this->user)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->post(route('user-payreqs.reimburse.store_detail'), $this->storeDetailPayload([
                'activity_id' => $activity->id,
            ]))
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('realization_details', [
            'realization_id' => $this->realization->id,
            'description' => 'Biaya transport',
            'activity_id' => $activity->id,
            'activity_excluded' => false,
        ]);
    }

    public function test_store_detail_with_activity_excluded_is_persisted(): void
    {
        $this->actingAs($this->user)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->post(route('user-payreqs.reimburse.store_detail'), $this->storeDetailPayload([
                'activity_excluded' => 1,
            ]))
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('realization_details', [
            'realization_id' => $this->realization->id,
            'description' => 'Biaya transport',
            'activity_id' => null,
            'activity_excluded' => true,
        ]);
    }

    public function test_store_detail_rejects_invalid_activity_id(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('user-payreqs.reimburse.store_detail'), $this->storeDetailPayload([
                'activity_id' => 99999,
            ]));

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['activity_id']);

        $this->assertDatabaseCount('realization_details', 0);
    }
}
