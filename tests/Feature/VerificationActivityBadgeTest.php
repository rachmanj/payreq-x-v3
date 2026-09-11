<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Activity;
use App\Models\Department;
use App\Models\Payreq;
use App\Models\Realization;
use App\Models\RealizationDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class VerificationActivityBadgeTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Department $department;

    protected function setUp(): void
    {
        parent::setUp();

        Role::query()->firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        $this->department = Department::query()->create([
            'department_name' => 'Finance',
            'akronim' => 'FIN',
            'sap_code' => 'FIN-01',
            'is_active' => true,
            'is_selectable' => true,
        ]);

        $this->admin = User::factory()->create([
            'project' => '000H',
            'department_id' => $this->department->id,
        ]);
        $this->admin->assignRole('admin');
    }

    public function test_edit_page_shows_header_and_row_activity_badges_for_reklasifikasi_realization(): void
    {
        $requestor = User::factory()->create([
            'project' => '000H',
            'department_id' => $this->department->id,
        ]);

        $activityAccount = Account::query()->create([
            'account_number' => '51112001',
            'account_name' => 'Mobilisasi',
            'type' => 'expense',
            'project' => '000H',
            'is_active' => true,
            'is_hidden' => false,
        ]);

        $activity = Activity::query()->create([
            'code' => 'KEG-2026-001',
            'name' => 'Mobilisasi Project X',
            'periode' => '2026-09',
            'mode' => 'reklasifikasi',
            'status' => 'open',
            'account_id' => $activityAccount->id,
            'project' => '000H',
            'created_by' => $this->admin->id,
        ]);

        $realization = $this->makeRealization($requestor, [
            'activity_id' => $activity->id,
        ]);

        RealizationDetail::query()->create([
            'realization_id' => $realization->id,
            'description' => 'Biaya transport bertag',
            'amount' => 300000,
            'project' => '000H',
            'department_id' => $this->department->id,
            'activity_id' => $activity->id,
            'activity_excluded' => false,
            'account_id' => null,
        ]);

        RealizationDetail::query()->create([
            'realization_id' => $realization->id,
            'description' => 'Biaya makan ikut header',
            'amount' => 200000,
            'project' => '000H',
            'department_id' => $this->department->id,
            'activity_id' => null,
            'activity_excluded' => false,
            'account_id' => null,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('verifications.edit', $realization->id));

        $response->assertOk();
        $response->assertSee('Kegiatan: KEG-2026-001 — Mobilisasi Project X', false);
        $response->assertSee('Reklasifikasi → 51112001 — Mobilisasi', false);
        $response->assertSee('KEG-2026-001 — Mobilisasi Project X', false);
        $response->assertSee('Ikut kegiatan: KEG-2026-001 — Mobilisasi Project X', false);
        $response->assertSee('akun belum dipilih', false);
    }

    public function test_edit_page_shows_override_badge_for_excluded_row(): void
    {
        $requestor = User::factory()->create([
            'project' => '000H',
            'department_id' => $this->department->id,
        ]);

        $activity = Activity::query()->create([
            'code' => 'KEG-2026-002',
            'name' => 'Kegiatan Header',
            'periode' => '2026-09',
            'mode' => 'tanpa_reklasifikasi',
            'status' => 'open',
            'project' => '000H',
            'created_by' => $this->admin->id,
        ]);

        $realization = $this->makeRealization($requestor, [
            'activity_id' => $activity->id,
        ]);

        RealizationDetail::query()->create([
            'realization_id' => $realization->id,
            'description' => 'Baris dikecualikan',
            'amount' => 100000,
            'project' => '000H',
            'department_id' => $this->department->id,
            'activity_id' => null,
            'activity_excluded' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('verifications.edit', $realization->id));

        $response->assertOk();
        $response->assertSee('Tanpa kegiatan (override)', false);
    }

    public function test_edit_page_without_activity_shows_neutral_header_and_no_row_badges(): void
    {
        $requestor = User::factory()->create([
            'project' => '000H',
            'department_id' => $this->department->id,
        ]);

        $realization = $this->makeRealization($requestor);

        RealizationDetail::query()->create([
            'realization_id' => $realization->id,
            'description' => 'Biaya umum',
            'amount' => 150000,
            'project' => '000H',
            'department_id' => $this->department->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('verifications.edit', $realization->id));

        $response->assertOk();
        $response->assertSee('Verification Details', false);
        $response->assertSee('Tanpa kegiatan', false);
        $response->assertDontSee('Ikut kegiatan:', false);
        $response->assertDontSee('Tanpa kegiatan (override)', false);
        $response->assertDontSee('Reklasifikasi →', false);
    }

    public function test_guest_is_redirected_from_verification_edit(): void
    {
        $requestor = User::factory()->create(['project' => '000H']);
        $realization = $this->makeRealization($requestor);

        $this->get(route('verifications.edit', $realization->id))
            ->assertRedirect(route('login'));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeRealization(User $requestor, array $overrides = []): Realization
    {
        $payreq = Payreq::query()->create([
            'user_id' => $requestor->id,
            'nomor' => 'PRQ-'.uniqid(),
            'type' => 'advance',
            'status' => 'paid',
            'amount' => 1000000,
            'project' => '000H',
            'department_id' => $this->department->id,
            'remarks' => 'Test payreq',
        ]);

        return Realization::query()->create(array_merge([
            'nomor' => 'VER-'.uniqid(),
            'payreq_id' => $payreq->id,
            'user_id' => $requestor->id,
            'project' => '000H',
            'department_id' => $this->department->id,
            'status' => 'approved',
            'verification_journal_id' => null,
        ], $overrides));
    }
}
