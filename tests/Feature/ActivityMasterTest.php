<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Activity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ActivityMasterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'manage_activities', 'guard_name' => 'web']);
        Role::query()->firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        Role::query()->firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    }

    protected function authorizedUser(): User
    {
        $user = User::factory()->create(['project' => '000H']);
        $user->assignRole('admin');
        $user->givePermissionTo('manage_activities');

        return $user;
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

    public function test_guest_cannot_access_activity_master(): void
    {
        $this->get(route('activities.index'))->assertRedirect(route('login'));
    }

    public function test_user_without_permission_cannot_access_activity_master(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('activities.index'));

        $this->assertNotEquals(200, $response->getStatusCode());
    }

    public function test_can_create_activity_tanpa_reklasifikasi(): void
    {
        $user = $this->authorizedUser();

        $this->actingAs($user)
            ->post(route('activities.store'), [
                'name' => 'Mobilisasi Excavator',
                'periode' => '2026-09',
                'mode' => 'tanpa_reklasifikasi',
            ])
            ->assertRedirect(route('activities.index'));

        $this->assertDatabaseHas('activities', [
            'name' => 'Mobilisasi Excavator',
            'mode' => 'tanpa_reklasifikasi',
            'status' => 'open',
            'created_by' => $user->id,
        ]);

        $activity = Activity::query()->first();
        $this->assertStringStartsWith('KEG-'.now()->format('Y').'-', $activity->code);
    }

    public function test_reklasifikasi_requires_expense_account(): void
    {
        $user = $this->authorizedUser();
        $assetAccount = Account::query()->create([
            'account_number' => '15001001',
            'account_name' => 'Fixed Asset',
            'type' => 'asset',
            'project' => '000H',
            'is_active' => true,
            'is_hidden' => false,
        ]);

        $this->actingAs($user)
            ->from(route('activities.create'))
            ->post(route('activities.store'), [
                'name' => 'Kegiatan Aset',
                'periode' => '2026-09',
                'mode' => 'reklasifikasi',
                'account_id' => $assetAccount->id,
            ])
            ->assertSessionHasErrors('account_id');

        $this->actingAs($user)
            ->from(route('activities.create'))
            ->post(route('activities.store'), [
                'name' => 'Kegiatan Tanpa Akun',
                'periode' => '2026-09',
                'mode' => 'reklasifikasi',
            ])
            ->assertSessionHasErrors('account_id');
    }

    public function test_can_create_reklasifikasi_activity_with_expense_account(): void
    {
        $user = $this->authorizedUser();
        $account = $this->expenseAccount();

        $this->actingAs($user)
            ->post(route('activities.store'), [
                'name' => 'Mobilisasi Project X',
                'periode' => '2026-09',
                'mode' => 'reklasifikasi',
                'account_id' => $account->id,
            ])
            ->assertRedirect(route('activities.index'));

        $this->assertDatabaseHas('activities', [
            'name' => 'Mobilisasi Project X',
            'mode' => 'reklasifikasi',
            'account_id' => $account->id,
        ]);
    }

    public function test_can_close_open_activity(): void
    {
        $user = $this->authorizedUser();
        $activity = Activity::query()->create([
            'code' => 'KEG-2026-001',
            'name' => 'Test',
            'periode' => '2026-09',
            'mode' => 'tanpa_reklasifikasi',
            'status' => 'open',
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->post(route('activities.close', $activity))
            ->assertRedirect(route('activities.index'));

        $this->assertSame('closed', $activity->fresh()->status);
    }

    public function test_can_create_activity_with_annual_periode(): void
    {
        $user = $this->authorizedUser();

        $this->actingAs($user)
            ->post(route('activities.store'), [
                'name' => 'Kegiatan Tahunan',
                'periode' => '2026',
                'mode' => 'tanpa_reklasifikasi',
            ])
            ->assertRedirect(route('activities.index'));

        $this->assertDatabaseHas('activities', [
            'name' => 'Kegiatan Tahunan',
            'periode' => '2026',
        ]);
    }

    public function test_invalid_periode_format_is_rejected(): void
    {
        $user = $this->authorizedUser();

        $this->actingAs($user)
            ->from(route('activities.create'))
            ->post(route('activities.store'), [
                'name' => 'Kegiatan Invalid Slash',
                'periode' => '2026/09',
                'mode' => 'tanpa_reklasifikasi',
            ])
            ->assertSessionHasErrors([
                'periode' => 'Periode harus dalam format YYYY-MM atau YYYY.',
            ]);

        $this->actingAs($user)
            ->from(route('activities.create'))
            ->post(route('activities.store'), [
                'name' => 'Kegiatan Invalid Text',
                'periode' => 'Sept 2026',
                'mode' => 'tanpa_reklasifikasi',
            ])
            ->assertSessionHasErrors([
                'periode' => 'Periode harus dalam format YYYY-MM atau YYYY.',
            ]);
    }

    public function test_invalid_periode_month_is_rejected(): void
    {
        $user = $this->authorizedUser();

        $this->actingAs($user)
            ->from(route('activities.create'))
            ->post(route('activities.store'), [
                'name' => 'Kegiatan Bulan Invalid',
                'periode' => '2026-13',
                'mode' => 'tanpa_reklasifikasi',
            ])
            ->assertSessionHasErrors([
                'periode' => 'Periode harus dalam format YYYY-MM atau YYYY.',
            ]);
    }

    public function test_create_form_shows_periode_select_with_current_month(): void
    {
        $user = $this->authorizedUser();
        $currentMonth = now()->format('Y-m');

        $response = $this->actingAs($user)->get(route('activities.create'));

        $response->assertOk();
        $response->assertSee('<select name="periode"', false);
        $response->assertSee('value="'.$currentMonth.'"', false);
    }

    public function test_edit_form_preserves_legacy_periode_not_in_standard_options(): void
    {
        $user = $this->authorizedUser();
        $activity = Activity::query()->create([
            'code' => 'KEG-2020-001',
            'name' => 'Kegiatan Lama',
            'periode' => '2020-03',
            'mode' => 'tanpa_reklasifikasi',
            'status' => 'open',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('activities.edit', $activity));

        $response->assertOk();
        $response->assertSee('value="2020-03"', false);
        $response->assertSee('value="2020-03" selected', false);
    }
}
