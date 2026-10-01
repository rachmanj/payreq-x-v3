<?php

namespace Tests\Feature;

use App\Http\Controllers\Reports\OngoingDashboardController;
use App\Models\Outgoing;
use App\Models\Payreq;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OngoingDashboardOutstandingAdvanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_payreq_belum_realisasi_excludes_finished_advance_but_keeps_split_outstanding(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-01 12:00:00'));

        $user = User::factory()->create(['project' => '021C']);

        $finishedPaid = Payreq::query()->create([
            'user_id' => $user->id,
            'nomor' => 'ADV-DASH-DONE',
            'type' => 'advance',
            'status' => 'paid',
            'amount' => 1_000_000,
            'due_date' => '2026-09-01',
            'project' => '021C',
            'remarks' => 'Sudah close',
        ]);
        $finishedPaid->realization()->create([
            'nomor' => 'REAL-DASH-DONE',
            'user_id' => $user->id,
            'project' => '021C',
            'status' => 'close',
        ]);
        Outgoing::query()->create([
            'payreq_id' => $finishedPaid->id,
            'amount' => 1_000_000,
            'outgoing_date' => '2026-08-01',
        ]);

        $splitOpen = Payreq::query()->create([
            'user_id' => $user->id,
            'nomor' => 'ADV-DASH-SPLIT',
            'type' => 'advance',
            'status' => 'split',
            'amount' => 800_000,
            'due_date' => '2026-10-15',
            'project' => '021C',
            'remarks' => 'Split belum lunas realisasi',
        ]);
        Outgoing::query()->create([
            'payreq_id' => $splitOpen->id,
            'amount' => 400_000,
            'outgoing_date' => '2026-09-10',
        ]);

        $reimbursePaid = Payreq::query()->create([
            'user_id' => $user->id,
            'nomor' => 'REIM-DASH-1',
            'type' => 'reimburse',
            'status' => 'paid',
            'amount' => 200_000,
            'project' => '021C',
            'remarks' => 'Bukan advance',
        ]);
        Outgoing::query()->create([
            'payreq_id' => $reimbursePaid->id,
            'amount' => 200_000,
            'outgoing_date' => '2026-09-01',
        ]);

        $controller = app(OngoingDashboardController::class);

        $this->assertEquals(400_000, $controller->payreq_belum_realisasi_amount('021C'));
        $this->assertEquals(400_000, $controller->payreqs_belum_realisasi_by_user_amount($user->id));

        Carbon::setTestNow();
    }
}
