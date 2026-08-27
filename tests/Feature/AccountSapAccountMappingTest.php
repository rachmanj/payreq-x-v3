<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountSapAccountMappingTest extends TestCase
{
    use RefreshDatabase;

    public function test_accounts_index_includes_sap_account_input(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('accounts.index'))
            ->assertOk()
            ->assertSee('SAP Account No', false)
            ->assertSee('name="sap_account"', false);
    }

    public function test_store_persists_sap_account(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('accounts.store'), [
                'account_number' => '149.0004194751',
                'account_name' => 'Mandiri IDR HO',
                'type' => 'bank',
                'project' => '000H',
                'sap_account' => ' 11201001 ',
                'description' => 'HO Mandiri',
            ])
            ->assertRedirect(route('accounts.index'));

        $this->assertDatabaseHas('accounts', [
            'account_number' => '149.0004194751',
            'sap_account' => '11201001',
        ]);
    }

    public function test_update_persists_sap_account(): void
    {
        $account = Account::query()->create([
            'account_number' => '149.0004194751',
            'account_name' => 'Mandiri IDR HO',
            'type' => 'bank',
            'project' => '000H',
            'sap_account' => null,
            'is_active' => true,
        ]);

        $this->actingAs(User::factory()->create())
            ->put(route('accounts.update', $account->id), [
                'account_number' => $account->account_number,
                'account_name' => $account->account_name,
                'type' => 'bank',
                'project' => '000H',
                'sap_account' => '11201001',
                'is_active' => '1',
            ])
            ->assertRedirect(route('accounts.index'));

        $this->assertDatabaseHas('accounts', [
            'id' => $account->id,
            'sap_account' => '11201001',
        ]);
    }

    public function test_update_clears_blank_sap_account_to_null(): void
    {
        $account = Account::query()->create([
            'account_number' => '149.0004194751',
            'account_name' => 'Mandiri IDR HO',
            'type' => 'bank',
            'project' => '000H',
            'sap_account' => '11201001',
            'is_active' => true,
        ]);

        $this->actingAs(User::factory()->create())
            ->put(route('accounts.update', $account->id), [
                'account_number' => $account->account_number,
                'account_name' => $account->account_name,
                'type' => 'bank',
                'project' => '000H',
                'sap_account' => '   ',
                'is_active' => '1',
            ])
            ->assertRedirect(route('accounts.index'));

        $this->assertNull($account->fresh()->sap_account);
    }

    public function test_accounts_data_includes_sap_account_in_action_html(): void
    {
        Account::query()->create([
            'account_number' => '149.0004194751',
            'account_name' => 'Mandiri IDR HO',
            'type' => 'bank',
            'project' => '000H',
            'sap_account' => '11201001',
            'is_active' => true,
        ]);

        $response = $this->actingAs(User::factory()->create(['project' => '000H']))
            ->getJson(route('accounts.data'))
            ->assertOk();

        $row = $response->json('data.0');

        $this->assertSame('11201001', $row['sap_account']);
        $this->assertStringContainsString('SAP Account No', $row['action']);
        $this->assertStringContainsString('name="sap_account"', $row['action']);
        $this->assertStringContainsString('11201001', $row['action']);
    }
}
