<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\DocumentNumber;
use App\Models\Pcbc;
use App\Models\User;
use App\Services\SapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PcbcSapAmountTest extends TestCase
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

        Role::query()->firstOrCreate(['name' => 'cashier'], ['guard_name' => 'web']);
    }

    protected function seedCashAccount(string $project = '021C'): void
    {
        Account::query()->create([
            'type' => 'cash',
            'account_number' => 'PC-'.$project,
            'account_name' => 'PC '.$project,
            'project' => $project,
            'sap_account' => '11101099',
            'app_balance' => 5000000,
            'is_active' => true,
        ]);

        DocumentNumber::query()->create([
            'document_type' => 'pcbc',
            'project' => $project,
            'year' => (int) date('Y'),
            'last_number' => 0,
        ]);
    }

    protected function validPayload(): array
    {
        return [
            'pcbc_date' => now()->toDateString(),
            'project' => '021C',
            'kertas_100rb' => 1,
            'system_amount' => '1.000.000,00',
            'fisik_amount' => '100000',
            'pemeriksa1' => 'Pemeriksa A',
            'approved_by' => 'Approver B',
        ];
    }

    public function test_store_sets_sap_amount_from_sap_service(): void
    {
        $this->seedCashAccount();

        $user = User::factory()->create(['project' => '021C']);
        $user->assignRole('cashier');

        $sapMock = Mockery::mock(SapService::class);
        $sapMock->shouldReceive('getChartOfAccountSystemBalance')
            ->once()
            ->with('11101099')
            ->andReturn(1234567.89);
        $this->app->instance(SapService::class, $sapMock);

        $response = $this->actingAs($user)->post(route('cashier.pcbc.store'), $this->validPayload());

        $response->assertRedirect();

        $pcbc = Pcbc::query()->latest('id')->first();
        $this->assertNotNull($pcbc);
        $this->assertEqualsWithDelta(1234567.89, (float) $pcbc->sap_amount, 0.01);
    }

    public function test_store_leaves_sap_amount_null_when_sap_unavailable(): void
    {
        $this->seedCashAccount();

        $user = User::factory()->create(['project' => '021C']);
        $user->assignRole('cashier');

        $sapMock = Mockery::mock(SapService::class);
        $sapMock->shouldReceive('getChartOfAccountSystemBalance')
            ->once()
            ->andThrow(new \RuntimeException('SAP unreachable'));
        $this->app->instance(SapService::class, $sapMock);

        $response = $this->actingAs($user)->post(route('cashier.pcbc.store'), $this->validPayload());

        $response->assertRedirect();

        $pcbc = Pcbc::query()->latest('id')->first();
        $this->assertNotNull($pcbc);
        $this->assertNull($pcbc->sap_amount);
    }

    public function test_fetch_sap_balance_updates_pcbc(): void
    {
        $this->seedCashAccount();

        $user = User::factory()->create(['project' => '021C']);
        $user->assignRole('cashier');

        $pcbc = Pcbc::query()->create([
            'nomor' => 'TEST-PCBC',
            'pcbc_date' => now()->toDateString(),
            'created_by' => $user->id,
            'project' => '021C',
            'system_amount' => 1000,
            'fisik_amount' => 1000,
            'sap_amount' => null,
            'pemeriksa1' => 'P1',
            'approved_by' => 'A1',
        ]);

        $sapMock = Mockery::mock(SapService::class);
        $sapMock->shouldReceive('getChartOfAccountSystemBalance')
            ->once()
            ->with('11101099')
            ->andReturn(999.5);
        $this->app->instance(SapService::class, $sapMock);

        $response = $this->actingAs($user)->post(route('cashier.pcbc.fetch_sap_balance', $pcbc->id));

        $response->assertRedirect();
        $pcbc->refresh();
        $this->assertEqualsWithDelta(999.5, (float) $pcbc->sap_amount, 0.01);
    }
}
