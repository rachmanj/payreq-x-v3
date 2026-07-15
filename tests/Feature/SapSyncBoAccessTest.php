<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VerificationJournal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SapSyncBoAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'akses_sap_sync'], ['guard_name' => 'web']);

        foreach (['superadmin', 'admin', 'cashier', 'approver', 'approver_bo', 'cashier_bo'] as $roleName) {
            Role::query()->firstOrCreate(['name' => $roleName], ['guard_name' => 'web']);
        }
    }

    protected function createBoUser(string $role = 'cashier_bo'): User
    {
        $user = User::factory()->create(['project' => '001H']);
        $user->assignRole($role);
        $user->givePermissionTo('akses_sap_sync');

        return $user;
    }

    protected function createVerificationJournal(string $project, ?User $creator = null): VerificationJournal
    {
        $creator ??= User::factory()->create();

        return VerificationJournal::query()->create([
            'nomor' => 'TESTVJ'.uniqid(),
            'date' => now()->toDateString(),
            'project' => $project,
            'amount' => 1000,
            'created_by' => $creator->id,
            'sap_submission_attempts' => 0,
        ]);
    }

    public function test_bo_user_is_redirected_to_001h_tab_from_dashboard(): void
    {
        $user = $this->createBoUser('approver_bo');

        $this->actingAs($user)
            ->get(route('accounting.sap-sync.index', ['page' => 'dashboard']))
            ->assertRedirect(route('accounting.sap-sync.index', ['page' => '001H']));
    }

    public function test_bo_user_can_access_001h_sap_sync_page(): void
    {
        $user = $this->createBoUser('cashier_bo');

        $this->actingAs($user)
            ->get(route('accounting.sap-sync.index', ['page' => '001H']))
            ->assertOk()
            ->assertSee('BO Jkt', false);
    }

    public function test_bo_user_cannot_view_non_001h_verification_journal(): void
    {
        $user = $this->createBoUser('approver_bo');
        $journal = $this->createVerificationJournal('017C');

        $this->actingAs($user)
            ->get(route('accounting.sap-sync.show', $journal->id))
            ->assertForbidden();
    }

    public function test_bo_user_can_view_001h_verification_journal_and_see_submit_button(): void
    {
        $user = $this->createBoUser('cashier_bo');
        $journal = $this->createVerificationJournal('001H', $user);

        $this->actingAs($user)
            ->get(route('accounting.sap-sync.show', $journal->id))
            ->assertOk()
            ->assertSee('Submit to SAP B1', false);
    }

    public function test_bo_user_cannot_load_data_for_other_projects(): void
    {
        $user = $this->createBoUser('approver_bo');

        $this->actingAs($user)
            ->get(route('accounting.sap-sync.data', ['project' => '017C']))
            ->assertForbidden();
    }

    public function test_bo_user_can_load_data_for_001h_project(): void
    {
        $user = $this->createBoUser('cashier_bo');
        $this->createVerificationJournal('001H');

        $this->actingAs($user)
            ->get(route('accounting.sap-sync.data', ['project' => '001H']))
            ->assertOk();
    }

    public function test_admin_user_can_still_access_other_project_tabs(): void
    {
        $user = User::factory()->create(['project' => '000H']);
        $user->assignRole('admin');
        $user->givePermissionTo('akses_sap_sync');

        $this->actingAs($user)
            ->get(route('accounting.sap-sync.index', ['page' => '017C']))
            ->assertOk();
    }
}
