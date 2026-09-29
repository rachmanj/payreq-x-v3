<?php

namespace Tests\Unit;

use App\Models\Account;
use App\Services\PettyCashSapBalanceService;
use App\Services\SapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class PettyCashSapBalanceServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_returns_balance_when_sap_responds(): void
    {
        Account::query()->create([
            'type' => 'cash',
            'account_number' => 'PC-021C',
            'account_name' => 'PC 021C',
            'project' => '021C',
            'sap_account' => '11101099',
            'app_balance' => 1,
            'is_active' => true,
        ]);

        $sapMock = Mockery::mock(SapService::class);
        $sapMock->shouldReceive('getChartOfAccountSystemBalance')
            ->once()
            ->with('11101099')
            ->andReturn(133543743.15);

        $this->app->instance(SapService::class, $sapMock);

        $result = app(PettyCashSapBalanceService::class)->getBalanceForProject('021C', true);

        $this->assertTrue($result['available']);
        $this->assertSame(133543743.15, $result['balance']);
        $this->assertSame('11101099', $result['sap_account']);
    }

    public function test_returns_unavailable_when_sap_returns_null(): void
    {
        Account::query()->create([
            'type' => 'cash',
            'account_number' => 'PC-021C',
            'account_name' => 'PC 021C',
            'project' => '021C',
            'sap_account' => '11101099',
            'app_balance' => 1,
            'is_active' => true,
        ]);

        $sapMock = Mockery::mock(SapService::class);
        $sapMock->shouldReceive('getChartOfAccountSystemBalance')
            ->once()
            ->with('11101099')
            ->andReturn(null);

        $this->app->instance(SapService::class, $sapMock);

        $result = app(PettyCashSapBalanceService::class)->getBalanceForProject('021C', true);

        $this->assertFalse($result['available']);
        $this->assertNull($result['balance']);
    }

    public function test_returns_unavailable_when_sap_throws(): void
    {
        Account::query()->create([
            'type' => 'cash',
            'account_number' => 'PC-021C',
            'account_name' => 'PC 021C',
            'project' => '021C',
            'sap_account' => '11101099',
            'app_balance' => 1,
            'is_active' => true,
        ]);

        $sapMock = Mockery::mock(SapService::class);
        $sapMock->shouldReceive('getChartOfAccountSystemBalance')
            ->once()
            ->with('11101099')
            ->andThrow(new \RuntimeException('SAP down'));

        $this->app->instance(SapService::class, $sapMock);

        $result = app(PettyCashSapBalanceService::class)->getBalanceForProject('021C', true);

        $this->assertFalse($result['available']);
        $this->assertNull($result['balance']);
    }

    public function test_uses_cache_on_second_call(): void
    {
        Account::query()->create([
            'type' => 'cash',
            'account_number' => 'PC-021C',
            'account_name' => 'PC 021C',
            'project' => '021C',
            'sap_account' => '11101099',
            'app_balance' => 1,
            'is_active' => true,
        ]);

        Cache::flush();

        $sapMock = Mockery::mock(SapService::class);
        $sapMock->shouldReceive('getChartOfAccountSystemBalance')
            ->once()
            ->with('11101099')
            ->andReturn(1000.0);

        $this->app->instance(SapService::class, $sapMock);

        $service = app(PettyCashSapBalanceService::class);
        $first = $service->getBalanceForProject('021C', true);
        $second = $service->getBalanceForProject('021C', false);

        $this->assertTrue($first['available']);
        $this->assertTrue($second['available']);
        $this->assertSame(1000.0, $second['balance']);
    }
}
