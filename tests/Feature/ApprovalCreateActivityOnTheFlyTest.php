<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Activity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ApprovalCreateActivityOnTheFlyTest extends TestCase
{
    use RefreshDatabase;

    protected User $approver;

    protected User $plainUser;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'manage_activities', 'guard_name' => 'web']);
        Role::query()->firstOrCreate(['name' => 'approver', 'guard_name' => 'web']);

        $this->approver = User::factory()->create(['project' => '000H']);
        $this->approver->assignRole('approver');
        $this->approver->givePermissionTo('manage_activities');

        $this->plainUser = User::factory()->create(['project' => '000H']);
    }

    protected function expenseAccount(string $number = '51112001'): Account
    {
        return Account::query()->create([
            'account_number' => $number,
            'account_name' => 'Mobilisation',
            'type' => 'expense',
            'project' => '000H',
            'is_active' => true,
            'is_hidden' => false,
        ]);
    }

    public function test_approver_can_create_activity_from_approval_endpoint(): void
    {
        $account = $this->expenseAccount();

        $response = $this->actingAs($this->approver)->postJson(route('approvals.activities.store'), [
            'name' => 'Kegiatan On The Fly',
            'periode' => '2026-09',
            'project' => '000H',
            'mode' => 'reklasifikasi',
            'account_id' => $account->id,
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('activity.name', 'Kegiatan On The Fly');

        $this->assertDatabaseHas('activities', [
            'name' => 'Kegiatan On The Fly',
            'mode' => 'reklasifikasi',
            'status' => 'open',
            'created_by' => $this->approver->id,
            'account_id' => $account->id,
        ]);

        $activity = Activity::query()->where('name', 'Kegiatan On The Fly')->first();
        $this->assertStringStartsWith('KEG-'.now()->format('Y').'-', $activity->code);
    }

    public function test_user_without_permission_cannot_create_activity_from_approval_endpoint(): void
    {
        $response = $this->actingAs($this->plainUser)->postJson(route('approvals.activities.store'), [
            'name' => 'Kegiatan Tanpa Izin',
            'periode' => '2026-09',
            'mode' => 'tanpa_reklasifikasi',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('activities', ['name' => 'Kegiatan Tanpa Izin']);
    }

    public function test_non_expense_account_rejected_for_reklasifikasi_mode(): void
    {
        $assetAccount = Account::query()->create([
            'account_number' => '15001001',
            'account_name' => 'Fixed Asset',
            'type' => 'asset',
            'project' => '000H',
            'is_active' => true,
            'is_hidden' => false,
        ]);

        $response = $this->actingAs($this->approver)->postJson(route('approvals.activities.store'), [
            'name' => 'Kegiatan Aset',
            'periode' => '2026-09',
            'mode' => 'reklasifikasi',
            'account_id' => $assetAccount->id,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['account_id']);

        $this->assertDatabaseMissing('activities', ['name' => 'Kegiatan Aset']);
    }

    public function test_open_activities_endpoint_returns_open_activities(): void
    {
        Activity::query()->create([
            'code' => 'KEG-2026-099',
            'name' => 'Open Activity',
            'periode' => '2026-09',
            'mode' => 'tanpa_reklasifikasi',
            'status' => 'open',
            'project' => '000H',
            'created_by' => $this->approver->id,
        ]);

        Activity::query()->create([
            'code' => 'KEG-2026-098',
            'name' => 'Closed Activity',
            'periode' => '2026-09',
            'mode' => 'tanpa_reklasifikasi',
            'status' => 'closed',
            'project' => '000H',
            'created_by' => $this->approver->id,
        ]);

        $response = $this->actingAs($this->approver)->getJson(route('approvals.activities.open', ['project' => '000H']));

        $response->assertOk();
        $response->assertJsonFragment(['code' => 'KEG-2026-099', 'name' => 'Open Activity']);
        $response->assertJsonMissing(['code' => 'KEG-2026-098']);
    }

    public function test_approver_can_create_activity_with_annual_periode_from_approval_endpoint(): void
    {
        $response = $this->actingAs($this->approver)->postJson(route('approvals.activities.store'), [
            'name' => 'Kegiatan Tahunan',
            'periode' => '2026',
            'mode' => 'tanpa_reklasifikasi',
        ]);

        $response->assertCreated()
            ->assertJsonPath('activity.name', 'Kegiatan Tahunan');

        $this->assertDatabaseHas('activities', [
            'name' => 'Kegiatan Tahunan',
            'periode' => '2026',
        ]);
    }

    public function test_invalid_periode_rejected_from_approval_endpoint(): void
    {
        $response = $this->actingAs($this->approver)->postJson(route('approvals.activities.store'), [
            'name' => 'Kegiatan Invalid',
            'periode' => '2026/09',
            'mode' => 'tanpa_reklasifikasi',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'periode' => 'Periode harus dalam format YYYY-MM atau YYYY.',
            ]);
    }
}
