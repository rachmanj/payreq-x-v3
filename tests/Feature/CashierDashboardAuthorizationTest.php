<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CashierDashboardAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $permission = Permission::firstOrCreate(
            ['name' => 'cashier_dashboard', 'guard_name' => 'web'],
            [],
        );

        foreach (['cashier', 'admin', 'superadmin', 'user'] as $roleName) {
            Role::query()->firstOrCreate(['name' => $roleName], ['guard_name' => 'web']);
        }

        foreach (['cashier', 'admin', 'superadmin'] as $roleName) {
            Role::findByName($roleName)->givePermissionTo($permission);
        }
    }

    protected function seedCashAccount(string $project = '000H'): void
    {
        Account::query()->create([
            'type' => 'cash',
            'account_number' => '11010101',
            'account_name' => 'Cash Account',
            'project' => $project,
            'app_balance' => 10000000,
            'is_active' => true,
        ]);
    }

    public function test_cashier_role_can_access_dashboard(): void
    {
        $this->seedCashAccount();
        $user = User::factory()->create(['project' => '000H']);
        $user->assignRole('cashier');

        $this->actingAs($user)
            ->get(route('cashier.dashboard.index'))
            ->assertOk();
    }

    public function test_superadmin_role_can_access_dashboard(): void
    {
        $this->seedCashAccount();
        $user = User::factory()->create(['project' => '000H']);
        $user->assignRole('superadmin');

        $this->actingAs($user)
            ->get(route('cashier.dashboard.index'))
            ->assertOk();
    }

    public function test_admin_role_can_access_dashboard(): void
    {
        $this->seedCashAccount();
        $user = User::factory()->create(['project' => '000H']);
        $user->assignRole('admin');

        $this->actingAs($user)
            ->get(route('cashier.dashboard.index'))
            ->assertOk();
    }

    public function test_user_without_permission_cannot_access_dashboard(): void
    {
        $user = User::factory()->create(['project' => '000H']);
        $user->assignRole('user');

        $this->actingAs($user)
            ->from('/home')
            ->get(route('cashier.dashboard.index'))
            ->assertRedirect('/home')
            ->assertSessionHas('alert_type', 'error');
    }
}
