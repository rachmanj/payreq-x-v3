<?php

namespace Tests\Unit;

use App\Models\Account;
use App\Models\Parameter;
use App\Services\PettyCashAccountResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PettyCashAccountResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_falls_back_to_lowest_id_cash_account_when_parameter_missing(): void
    {
        $older = Account::query()->create([
            'type' => 'cash',
            'account_number' => 'OLD-025C',
            'account_name' => 'Old PC',
            'project' => '025C',
            'sap_account' => '11101006',
            'app_balance' => 100,
            'is_active' => true,
        ]);

        Account::query()->create([
            'type' => 'cash',
            'account_number' => 'NEW-025C',
            'account_name' => 'New PC',
            'project' => '025C',
            'sap_account' => '11101010',
            'app_balance' => 200,
            'is_active' => true,
        ]);

        $resolved = app(PettyCashAccountResolver::class)->resolveForProject('025C');

        $this->assertNotNull($resolved);
        $this->assertSame($older->id, $resolved->id);
        $this->assertSame('11101006', $resolved->sap_account);
    }

    public function test_uses_parameter_mapped_sap_account_when_configured(): void
    {
        $older = Account::query()->create([
            'type' => 'cash',
            'account_number' => 'OLD-022C',
            'account_name' => 'Old PC',
            'project' => '022C',
            'sap_account' => '11101006',
            'app_balance' => 100,
            'is_active' => true,
        ]);

        $newer = Account::query()->create([
            'type' => 'cash',
            'account_number' => 'NEW-022C',
            'account_name' => 'New PC',
            'project' => '022C',
            'sap_account' => '11101010',
            'app_balance' => 200,
            'is_active' => true,
        ]);

        Parameter::query()->create([
            'name1' => PettyCashAccountResolver::PARAMETER_NAME,
            'name2' => '022C',
            'param_value' => '11101010',
        ]);

        $resolved = app(PettyCashAccountResolver::class)->resolveForProject('022C');

        $this->assertNotNull($resolved);
        $this->assertSame($newer->id, $resolved->id);
        $this->assertNotSame($older->id, $resolved->id);
    }
}
