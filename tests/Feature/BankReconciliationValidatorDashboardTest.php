<?php

namespace Tests\Feature;

use App\Models\BankReconciliation;
use App\Models\Giro;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BankReconciliationValidatorDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'validate_bank_reconciliation'], ['guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'akses_koran'], ['guard_name' => 'web']);
        Role::query()->firstOrCreate(['name' => 'cashier'], ['guard_name' => 'web']);
        Role::query()->firstOrCreate(['name' => 'admin'], ['guard_name' => 'web']);
    }

    protected function createGiro(): Giro
    {
        $bankId = \Illuminate\Support\Facades\DB::table('banks')->insertGetId([
            'name' => 'BCA',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Giro::query()->create([
            'acc_no' => '1234567890',
            'acc_name' => 'Test Account',
            'bank_id' => $bankId,
            'project' => '000H',
        ]);
    }

    protected function createPreparer(): User
    {
        $user = User::factory()->create(['project' => '000H']);
        $user->assignRole('cashier');
        $user->givePermissionTo('akses_koran');

        return $user;
    }

    protected function createValidator(): User
    {
        $user = User::factory()->create(['project' => '000H']);
        $user->assignRole('admin');
        $user->givePermissionTo(['validate_bank_reconciliation', 'akses_koran']);

        return $user;
    }

    protected function createPendingReconciliation(User $preparer): BankReconciliation
    {
        return BankReconciliation::query()->create([
            'giro_id' => $this->createGiro()->id,
            'periode' => '2026-05-01',
            'source_mode' => BankReconciliation::SOURCE_MANUAL,
            'status' => BankReconciliation::STATUS_IN_REVIEW,
            'validation_status' => BankReconciliation::VALIDATION_PENDING,
            'created_by' => $preparer->id,
            'submitted_by' => $preparer->id,
            'submitted_at' => now(),
        ]);
    }

    public function test_dashboard_shows_pending_bank_reconciliation_count_for_validators(): void
    {
        $preparer = $this->createPreparer();
        $validator = $this->createValidator();

        $this->createPendingReconciliation($preparer);
        $this->createPendingReconciliation($preparer);

        $this->actingAs($validator)
            ->get(route('dashboard.index'))
            ->assertOk()
            ->assertSee('Bank reconciliation pending validation', false)
            ->assertSee('data-dashboard-pending-bank-reconciliation="2"', false);
    }

    public function test_dashboard_hides_bank_reconciliation_card_without_permission(): void
    {
        $user = $this->createPreparer();

        $this->actingAs($user)
            ->get(route('dashboard.index'))
            ->assertOk()
            ->assertDontSee('Bank reconciliation pending validation', false)
            ->assertDontSee('data-dashboard-pending-bank-reconciliation=', false);
    }

    public function test_pending_validation_index_lists_only_assignable_sessions(): void
    {
        $preparer = $this->createPreparer();
        $validator = $this->createValidator();

        $assignable = $this->createPendingReconciliation($preparer);

        BankReconciliation::query()->create([
            'giro_id' => $this->createGiro()->id,
            'periode' => '2026-04-01',
            'source_mode' => BankReconciliation::SOURCE_MANUAL,
            'status' => BankReconciliation::STATUS_IN_REVIEW,
            'validation_status' => BankReconciliation::VALIDATION_PENDING,
            'created_by' => $validator->id,
            'submitted_by' => $validator->id,
            'submitted_at' => now(),
        ]);

        $this->actingAs($validator)
            ->get(route('cashier.bank-reconciliation.index', ['view' => 'pending_validation']))
            ->assertOk()
            ->assertSee('Pending validation', false)
            ->assertSee('Validate', false)
            ->assertSee((string) $assignable->id, false);
    }
}
