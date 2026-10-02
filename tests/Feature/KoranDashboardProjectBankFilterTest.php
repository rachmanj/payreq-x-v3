<?php

namespace Tests\Feature;

use App\Http\Controllers\Cashier\KoranController;
use App\Models\Giro;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class KoranDashboardProjectBankFilterTest extends TestCase
{
    use RefreshDatabase;

    protected int $bankMandiriId;

    protected int $bankBcaId;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'akses_koran'], ['guard_name' => 'web']);

        Role::query()->firstOrCreate(['name' => 'cashier'], ['guard_name' => 'web']);
        Role::query()->firstOrCreate(['name' => 'payreq_user'], ['guard_name' => 'web']);

        $this->bankMandiriId = (int) DB::table('banks')->insertGetId([
            'name' => 'Mandiri',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->bankBcaId = (int) DB::table('banks')->insertGetId([
            'name' => 'BCA',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function createElevatedViewer(): User
    {
        $user = User::factory()->create(['project' => '000H']);
        $user->assignRole('cashier');
        $user->givePermissionTo('akses_koran');

        return $user;
    }

    protected function createProjectScopedViewer(string $project = '000H'): User
    {
        $user = User::factory()->create(['project' => $project]);
        $user->assignRole('payreq_user');
        $user->givePermissionTo('akses_koran');

        return $user;
    }

    protected function createGiro(string $accNo, string $project, int $bankId): Giro
    {
        return Giro::query()->create([
            'acc_no' => $accNo,
            'acc_name' => 'Account '.$accNo,
            'bank_id' => $bankId,
            'project' => $project,
        ]);
    }

    public function test_dashboard_without_filter_shows_all_accessible_giros(): void
    {
        $user = $this->createElevatedViewer();
        $giroA = $this->createGiro('1111111111', '000H', $this->bankMandiriId);
        $giroB = $this->createGiro('2222222222', '001X', $this->bankBcaId);

        $this->actingAs($user)
            ->get(route('cashier.koran.index', ['page' => 'dashboard', 'year' => 2026]))
            ->assertOk()
            ->assertSee($giroA->acc_no, false)
            ->assertSee($giroB->acc_no, false)
            ->assertSee('info-box-number">2<', false);
    }

    public function test_dashboard_project_filter_limits_grid_to_selected_project(): void
    {
        $user = $this->createElevatedViewer();
        $giroA = $this->createGiro('1111111111', '000H', $this->bankMandiriId);
        $giroB = $this->createGiro('2222222222', '001X', $this->bankBcaId);

        $this->actingAs($user)
            ->get(route('cashier.koran.index', [
                'page' => 'dashboard',
                'year' => 2026,
                'project' => '000H',
            ]))
            ->assertOk()
            ->assertSee($giroA->acc_no, false)
            ->assertDontSee($giroB->acc_no, false)
            ->assertSee('Project 000H', false);
    }

    public function test_dashboard_bank_filter_limits_grid_to_selected_bank(): void
    {
        $user = $this->createElevatedViewer();
        $giroA = $this->createGiro('1111111111', '000H', $this->bankMandiriId);
        $giroB = $this->createGiro('2222222222', '001X', $this->bankBcaId);

        $this->actingAs($user)
            ->get(route('cashier.koran.index', [
                'page' => 'dashboard',
                'year' => 2026,
                'bank' => $this->bankMandiriId,
            ]))
            ->assertOk()
            ->assertSee($giroA->acc_no, false)
            ->assertDontSee($giroB->acc_no, false)
            ->assertSee('Bank Mandiri', false);
    }

    public function test_dashboard_project_and_bank_filters_combine(): void
    {
        $user = $this->createElevatedViewer();
        $match = $this->createGiro('1111111111', '000H', $this->bankMandiriId);
        $this->createGiro('3333333333', '000H', $this->bankBcaId);
        $this->createGiro('2222222222', '001X', $this->bankMandiriId);

        $this->actingAs($user)
            ->get(route('cashier.koran.index', [
                'page' => 'dashboard',
                'year' => 2026,
                'project' => '000H',
                'bank' => $this->bankMandiriId,
            ]))
            ->assertOk()
            ->assertSee($match->acc_no, false)
            ->assertDontSee('3333333333', false)
            ->assertDontSee('2222222222', false);
    }

    public function test_project_scoped_user_cannot_view_other_project_via_query_parameter(): void
    {
        $user = $this->createProjectScopedViewer('000H');
        $ownGiro = $this->createGiro('1111111111', '000H', $this->bankMandiriId);
        $otherGiro = $this->createGiro('2222222222', '001X', $this->bankBcaId);

        $this->actingAs($user)
            ->get(route('cashier.koran.index', [
                'page' => 'dashboard',
                'year' => 2026,
                'project' => '001X',
            ]))
            ->assertOk()
            ->assertSee($ownGiro->acc_no, false)
            ->assertDontSee($otherGiro->acc_no, false);
    }

    public function test_statistics_reflect_active_filters(): void
    {
        $user = $this->createElevatedViewer();
        $this->createGiro('1111111111', '000H', $this->bankMandiriId);
        $this->createGiro('2222222222', '001X', $this->bankBcaId);

        $this->actingAs($user);

        $unfiltered = app(KoranController::class)->check_koran_files('2026');
        $filtered = app(KoranController::class)->check_koran_files('2026', '000H');

        $unfilteredStats = (new \ReflectionClass(KoranController::class))
            ->getMethod('calculateStatistics')
            ->invoke(app(KoranController::class), $unfiltered);

        $filteredStats = (new \ReflectionClass(KoranController::class))
            ->getMethod('calculateStatistics')
            ->invoke(app(KoranController::class), $filtered);

        $this->assertSame(2, $unfilteredStats['total_accounts']);
        $this->assertSame(24, $unfilteredStats['total_months']);
        $this->assertSame(1, $filteredStats['total_accounts']);
        $this->assertSame(12, $filteredStats['total_months']);

        $this->get(route('cashier.koran.index', [
            'page' => 'dashboard',
            'year' => 2026,
            'project' => '000H',
        ]))
            ->assertOk()
            ->assertSee('info-box-number">1<', false)
            ->assertSee('info-box-number">0/12<', false);
    }
}
