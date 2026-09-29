<?php

namespace Tests\Feature;

use App\Http\Controllers\Reports\OngoingDashboardController;
use App\Models\Account;
use App\Models\User;
use App\Services\SapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CashierDashboardPcSapBalanceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'cashier_dashboard'], ['guard_name' => 'web']);
    }

    public function test_dashboard_data_includes_sap_balance_and_variance_level(): void
    {
        Account::query()->create([
            'type' => 'cash',
            'account_number' => 'PC-021C',
            'account_name' => 'PC',
            'project' => '021C',
            'sap_account' => '11101099',
            'app_balance' => 10_000_000,
            'is_active' => true,
        ]);

        Account::query()->create([
            'type' => 'advance',
            'account_number' => 'ADV-021C',
            'account_name' => 'Advance',
            'project' => '021C',
            'app_balance' => 0,
            'is_active' => true,
        ]);

        $sapMock = Mockery::mock(SapService::class);
        $sapMock->shouldReceive('getChartOfAccountSystemBalance')
            ->once()
            ->with('11101099')
            ->andReturn(9_999_000.0);
        $this->app->instance(SapService::class, $sapMock);

        $data = app(OngoingDashboardController::class)->dashboard_data('021C');

        $this->assertTrue($data['saldo_pc_sap_realtime_available']);
        $this->assertSame('9,999,000.00', $data['saldo_pc_sap_realtime']);
        $this->assertSame(1000.0, $data['selisih_cek_balance_sap_raw']);
        $this->assertSame('green', $data['selisih_cek_balance_sap_level']);
    }

    public function test_dashboard_shows_unavailable_when_sap_fails(): void
    {
        Account::query()->create([
            'type' => 'cash',
            'account_number' => 'PC-021C',
            'account_name' => 'PC',
            'project' => '021C',
            'sap_account' => '11101099',
            'app_balance' => 1,
            'is_active' => true,
        ]);

        $sapMock = Mockery::mock(SapService::class);
        $sapMock->shouldReceive('getChartOfAccountSystemBalance')
            ->once()
            ->andReturn(null);
        $this->app->instance(SapService::class, $sapMock);

        $data = app(OngoingDashboardController::class)->dashboard_data('021C');

        $this->assertFalse($data['saldo_pc_sap_realtime_available']);
        $this->assertNull($data['saldo_pc_sap_realtime']);
        $this->assertNull($data['selisih_cek_balance_sap_raw']);
        $this->assertNull($data['selisih_cek_balance_sap_level']);
    }

    public function test_cashier_dashboard_page_shows_indonesian_unavailable_text(): void
    {
        Account::query()->create([
            'type' => 'cash',
            'account_number' => 'PC-021C',
            'account_name' => 'PC',
            'project' => '021C',
            'sap_account' => '11101099',
            'app_balance' => 1,
            'is_active' => true,
        ]);

        $user = User::factory()->create(['project' => '021C']);
        $user->givePermissionTo('cashier_dashboard');

        $sapMock = Mockery::mock(SapService::class);
        $sapMock->shouldReceive('getChartOfAccountSystemBalance')
            ->once()
            ->andThrow(new \RuntimeException('down'));
        $this->app->instance(SapService::class, $sapMock);

        $response = $this->actingAs($user)->get(route('cashier.dashboard.index'));

        $response->assertOk();
        $response->assertSee('tidak tersedia', false);
        $response->assertDontSee('text-danger', false);
    }
}
