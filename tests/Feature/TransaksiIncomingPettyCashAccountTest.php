<?php

namespace Tests\Feature;

use App\Http\Controllers\Cashier\TransaksiController;
use App\Models\Account;
use App\Models\Incoming;
use App\Models\Parameter;
use App\Models\Transaksi;
use App\Models\User;
use App\Services\PettyCashAccountResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransaksiIncomingPettyCashAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_incoming_transaksi_uses_parameter_mapped_cash_account_for_022c(): void
    {
        Account::query()->create([
            'type' => 'cash',
            'account_number' => 'OLD-022C',
            'account_name' => 'Old PC',
            'project' => '022C',
            'sap_account' => '11101006',
            'app_balance' => 100,
            'is_active' => true,
        ]);

        $newCash = Account::query()->create([
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

        $user = User::factory()->create(['project' => '022C']);
        $this->actingAs($user);

        $incoming = Incoming::query()->create([
            'nomor' => 'IN-022C-1',
            'amount' => 50000,
            'project' => '022C',
            'receive_date' => '2026-09-29',
            'description' => 'Test incoming',
            'will_post' => true,
        ]);

        app(TransaksiController::class)->store('incoming', $incoming);

        $transaksi = Transaksi::query()->where('document_id', $incoming->id)->first();

        $this->assertNotNull($transaksi);
        $this->assertSame($newCash->id, $transaksi->account_id);
        $this->assertSame('11101010', $newCash->sap_account);
    }

    public function test_incoming_transaksi_falls_back_to_lowest_id_cash_when_parameter_missing(): void
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

        $user = User::factory()->create(['project' => '025C']);
        $this->actingAs($user);

        $incoming = Incoming::query()->create([
            'nomor' => 'IN-025C-1',
            'amount' => 25000,
            'project' => '025C',
            'receive_date' => '2026-09-29',
            'description' => 'Test incoming',
            'will_post' => true,
        ]);

        app(TransaksiController::class)->store('incoming', $incoming);

        $transaksi = Transaksi::query()->where('document_id', $incoming->id)->first();

        $this->assertNotNull($transaksi);
        $this->assertSame($older->id, $transaksi->account_id);
    }
}
