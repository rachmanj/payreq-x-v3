<?php

namespace Tests\Feature;

use App\Models\BankReconciliation;
use App\Models\Giro;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BankReconciliationAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'akses_koran'], ['guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'validate_bank_reconciliation'], ['guard_name' => 'web']);
        Role::query()->firstOrCreate(['name' => 'cashier'], ['guard_name' => 'web']);
        Role::query()->firstOrCreate(['name' => 'admin'], ['guard_name' => 'web']);
        Role::query()->firstOrCreate(['name' => 'staff'], ['guard_name' => 'web']);
    }

    protected function createGiro(string $project = '000H'): Giro
    {
        $bankId = DB::table('banks')->insertGetId([
            'name' => 'BCA',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Giro::query()->create([
            'acc_no' => '1234567890',
            'acc_name' => 'Test Account',
            'bank_id' => $bankId,
            'project' => $project,
        ]);
    }

    protected function createReconciliation(User $preparer, string $project = '000H'): BankReconciliation
    {
        return BankReconciliation::query()->create([
            'giro_id' => $this->createGiro($project)->id,
            'periode' => '2026-02-01',
            'source_mode' => BankReconciliation::SOURCE_MANUAL,
            'status' => BankReconciliation::STATUS_IN_REVIEW,
            'created_by' => $preparer->id,
        ]);
    }

    protected function createStaff(string $project = '000H'): User
    {
        $user = User::factory()->create(['project' => $project]);
        $user->assignRole('staff');
        $user->givePermissionTo('akses_koran');

        return $user;
    }

    public function test_user_without_akses_koran_cannot_open_index(): void
    {
        Role::query()->firstOrCreate(['name' => 'cashier'], ['guard_name' => 'web']);
        $user = User::factory()->create(['project' => '000H']);
        $user->assignRole('cashier');

        $this->actingAs($user)
            ->from('/home')
            ->get(route('cashier.bank-reconciliation.index'))
            ->assertRedirect('/home')
            ->assertSessionHas('alert_type', 'error');
    }

    public function test_user_without_akses_koran_cannot_open_show(): void
    {
        $owner = $this->createStaff();
        $reconciliation = $this->createReconciliation($owner);

        $user = User::factory()->create(['project' => '000H']);
        $user->assignRole('cashier');

        $this->actingAs($user)
            ->from('/home')
            ->get(route('cashier.bank-reconciliation.show', $reconciliation))
            ->assertRedirect('/home')
            ->assertSessionHas('alert_type', 'error');
    }

    public function test_non_elevated_user_cannot_view_other_project_reconciliation(): void
    {
        $owner = $this->createStaff('001H');
        $reconciliation = $this->createReconciliation($owner, '001H');

        $otherProjectUser = $this->createStaff('000H');

        $this->actingAs($otherProjectUser)
            ->get(route('cashier.bank-reconciliation.show', $reconciliation))
            ->assertForbidden();
    }

    public function test_non_elevated_user_can_view_own_project_reconciliation(): void
    {
        $owner = $this->createStaff('000H');
        $reconciliation = $this->createReconciliation($owner, '000H');

        $sameProjectUser = $this->createStaff('000H');

        $this->actingAs($sameProjectUser)
            ->get(route('cashier.bank-reconciliation.show', $reconciliation))
            ->assertOk();
    }

    public function test_elevated_cashier_can_view_other_project_reconciliation(): void
    {
        $owner = $this->createStaff('001H');
        $reconciliation = $this->createReconciliation($owner, '001H');

        $cashier = User::factory()->create(['project' => '000H']);
        $cashier->assignRole('cashier');
        $cashier->givePermissionTo('akses_koran');

        $this->actingAs($cashier)
            ->get(route('cashier.bank-reconciliation.show', $reconciliation))
            ->assertOk();
    }

    public function test_non_elevated_user_cannot_store_for_other_project_giro(): void
    {
        $otherGiro = $this->createGiro('001H');
        $user = $this->createStaff('000H');

        $this->actingAs($user)
            ->post(route('cashier.bank-reconciliation.store'), [
                'giro_id' => $otherGiro->id,
                'source_mode' => BankReconciliation::SOURCE_MANUAL,
                'periode' => '2026-03',
            ])
            ->assertForbidden();
    }

    public function test_index_hides_other_project_sessions_for_non_elevated_users(): void
    {
        $ownOwner = $this->createStaff('000H');
        $own = $this->createReconciliation($ownOwner, '000H');

        $otherOwner = $this->createStaff('001H');
        $other = $this->createReconciliation($otherOwner, '001H');

        $viewer = $this->createStaff('000H');

        $response = $this->actingAs($viewer)
            ->get(route('cashier.bank-reconciliation.index'))
            ->assertOk();

        $reconciliations = $response->viewData('reconciliations');
        $this->assertTrue($reconciliations->contains(fn ($row) => (int) $row->id === (int) $own->id));
        $this->assertFalse($reconciliations->contains(fn ($row) => (int) $row->id === (int) $other->id));
    }
}
