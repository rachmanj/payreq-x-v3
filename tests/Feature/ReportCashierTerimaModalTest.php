<?php

namespace Tests\Feature;

use App\Http\Controllers\Reports\ReportCashierController;
use App\Models\CashierModal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReportCashierTerimaModalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::query()->firstOrCreate(['name' => 'cashier'], ['guard_name' => 'web']);
    }

    public function test_get_today_terima_modal_returns_zero_when_no_bod_row_for_non_admin(): void
    {
        $user = User::factory()->create(['project' => '000H']);
        $user->assignRole('cashier');

        $this->actingAs($user);

        $amount = app(ReportCashierController::class)->getTodayTerimaModal();

        $this->assertSame(0, $amount);

        $this->get(route('reports.cashier.index'))
            ->assertOk()
            ->assertSee('Opening Balance');
    }

    public function test_get_today_terima_modal_returns_receive_amount_when_bod_row_exists(): void
    {
        $submitter = User::factory()->create(['project' => '000H']);
        $receiver = User::factory()->create(['project' => '000H']);
        $receiver->assignRole('cashier');

        CashierModal::create([
            'submitter' => $submitter->id,
            'receiver' => $receiver->id,
            'project' => '000H',
            'date' => now()->toDateString(),
            'type' => 'bod',
            'submit_amount' => 750000,
            'receive_amount' => 750000,
            'status' => 'close',
        ]);

        $this->actingAs($receiver);

        $amount = app(ReportCashierController::class)->getTodayTerimaModal();

        $this->assertEquals(750000, (float) $amount);
    }
}
