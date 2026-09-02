<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\CashierModal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CashierModalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['superadmin', 'admin', 'head_cashier', 'cashier'] as $roleName) {
            Role::query()->firstOrCreate(['name' => $roleName], ['guard_name' => 'web']);
        }
    }

    private function createCashAccount(string $project = '000H', float $balance = 10000000): void
    {
        Account::create([
            'type' => 'cash',
            'account_number' => '11010101',
            'account_name' => 'Cash Account',
            'project' => $project,
            'app_balance' => $balance,
            'is_active' => true,
        ]);
    }

    public function test_receive_by_intended_receiver_succeeds(): void
    {
        $submitter = User::factory()->create(['project' => '000H']);
        $receiver = User::factory()->create(['project' => '000H']);

        $modal = CashierModal::create([
            'submitter' => $submitter->id,
            'receiver' => $receiver->id,
            'project' => '000H',
            'date' => now()->toDateString(),
            'type' => 'bod',
            'submit_amount' => 500000,
            'status' => 'open',
        ]);

        $this->actingAs($receiver)
            ->put(route('cashier.modal.receive', $modal->id), [
                'receive_amount' => 500000,
                'remarks' => 'OK',
            ])
            ->assertRedirect();

        $modal->refresh();

        $this->assertSame('close', $modal->status);
        $this->assertEquals(500000, (float) $modal->receive_amount);
        $this->assertSame($receiver->id, (int) $modal->receiver);
    }

    public function test_receive_by_non_receiver_returns_403(): void
    {
        $submitter = User::factory()->create(['project' => '000H']);
        $receiver = User::factory()->create(['project' => '000H']);
        $otherUser = User::factory()->create(['project' => '000H']);

        $modal = CashierModal::create([
            'submitter' => $submitter->id,
            'receiver' => $receiver->id,
            'project' => '000H',
            'date' => now()->toDateString(),
            'type' => 'bod',
            'submit_amount' => 500000,
            'status' => 'open',
        ]);

        $this->actingAs($otherUser)
            ->put(route('cashier.modal.receive', $modal->id), [
                'receive_amount' => 500000,
            ])
            ->assertForbidden();

        $modal->refresh();
        $this->assertSame('open', $modal->status);
    }

    public function test_receive_closed_modal_returns_403(): void
    {
        $submitter = User::factory()->create(['project' => '000H']);
        $receiver = User::factory()->create(['project' => '000H']);

        $modal = CashierModal::create([
            'submitter' => $submitter->id,
            'receiver' => $receiver->id,
            'project' => '000H',
            'date' => now()->toDateString(),
            'type' => 'bod',
            'submit_amount' => 500000,
            'receive_amount' => 500000,
            'status' => 'close',
        ]);

        $this->actingAs($receiver)
            ->put(route('cashier.modal.receive', $modal->id), [
                'receive_amount' => 500000,
            ])
            ->assertForbidden();
    }

    public function test_store_bod_by_head_cashier_succeeds(): void
    {
        $this->createCashAccount();

        $headCashier = User::factory()->create(['project' => '000H']);
        $headCashier->assignRole('head_cashier');

        $cashier = User::factory()->create(['project' => '000H']);
        $cashier->assignRole('cashier');

        $this->actingAs($headCashier)
            ->post(route('cashier.modal.store'), [
                'date' => now()->toDateString(),
                'type' => 'bod',
                'submit_amount' => 1000000,
                'receiver' => $cashier->id,
                'remarks' => 'BOD test',
            ])
            ->assertRedirect(route('cashier.modal.index'));

        $modal = CashierModal::query()->latest('id')->first();

        $this->assertNotNull($modal);
        $this->assertSame('open', $modal->status);
        $this->assertSame('bod', $modal->type);
        $this->assertNull($modal->tx_in);
        $this->assertNull($modal->tx_out);
    }

    public function test_store_bod_by_cashier_returns_403(): void
    {
        $this->createCashAccount();

        $cashier = User::factory()->create(['project' => '000H']);
        $cashier->assignRole('cashier');

        $receiver = User::factory()->create(['project' => '000H']);

        $this->actingAs($cashier)
            ->post(route('cashier.modal.store'), [
                'date' => now()->toDateString(),
                'type' => 'bod',
                'submit_amount' => 1000000,
                'receiver' => $receiver->id,
            ])
            ->assertForbidden();

        $this->assertSame(0, CashierModal::count());
    }

    public function test_store_submit_amount_non_numeric_fails_validation(): void
    {
        $this->createCashAccount();

        $headCashier = User::factory()->create(['project' => '000H']);
        $headCashier->assignRole('head_cashier');

        $cashier = User::factory()->create(['project' => '000H']);

        $this->actingAs($headCashier)
            ->from(route('cashier.modal.index'))
            ->post(route('cashier.modal.store'), [
                'date' => now()->toDateString(),
                'type' => 'bod',
                'submit_amount' => 'abc',
                'receiver' => $cashier->id,
            ])
            ->assertSessionHasErrors('submit_amount');

        $this->assertSame(0, CashierModal::count());
    }

    public function test_admin_data_sees_all_modals(): void
    {
        $admin = User::factory()->create(['project' => '000H']);
        $admin->assignRole('superadmin');

        $userA = User::factory()->create(['project' => '000H']);
        $userB = User::factory()->create(['project' => '000H']);
        $userC = User::factory()->create(['project' => '000H']);

        CashierModal::create([
            'submitter' => $userA->id,
            'receiver' => $userB->id,
            'project' => '000H',
            'date' => now()->toDateString(),
            'type' => 'bod',
            'submit_amount' => 100000,
            'status' => 'open',
        ]);

        CashierModal::create([
            'submitter' => $userB->id,
            'receiver' => $userC->id,
            'project' => '000H',
            'date' => now()->toDateString(),
            'type' => 'eod',
            'submit_amount' => 200000,
            'status' => 'open',
        ]);

        $this->actingAs($admin)
            ->get(route('cashier.modal.data'))
            ->assertOk()
            ->assertJsonPath('recordsTotal', 2);
    }
}
