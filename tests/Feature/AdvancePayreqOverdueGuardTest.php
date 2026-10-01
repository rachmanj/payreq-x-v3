<?php

namespace Tests\Feature;

use App\Http\Controllers\CashierApprovedController;
use App\Models\Outgoing;
use App\Models\Payreq;
use App\Models\Realization;
use App\Models\User;
use App\Models\VerificationJournal;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdvancePayreqOverdueGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_paid_advance_with_closed_realization_is_not_counted_as_overdue(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-01 12:00:00'));

        $user = User::factory()->create();

        Payreq::query()->create([
            'user_id' => $user->id,
            'nomor' => '26010101009',
            'type' => 'advance',
            'status' => 'paid',
            'amount' => 800000,
            'due_date' => '2026-09-20',
            'project' => '001H',
            'remarks' => 'Sudah realisasi',
        ])->realization()->create([
            'nomor' => '26020100896',
            'user_id' => $user->id,
            'project' => '001H',
            'status' => 'close',
        ]);

        $this->actingAs($user)
            ->get(route('user-payreqs.index'))
            ->assertOk()
            ->assertViewHas('enable_payreq', true)
            ->assertViewHas('overdue_payreqs', 0);
    }

    public function test_paid_advance_without_finished_realization_still_blocks_new_payreq(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-01 12:00:00'));

        $user = User::factory()->create();

        Payreq::query()->create([
            'user_id' => $user->id,
            'nomor' => 'ADV-OD-1',
            'type' => 'advance',
            'status' => 'paid',
            'amount' => 500000,
            'due_date' => '2026-09-20',
            'project' => '001H',
            'remarks' => 'Belum realisasi',
        ]);

        $this->actingAs($user)
            ->get(route('user-payreqs.index'))
            ->assertOk()
            ->assertViewHas('enable_payreq', false)
            ->assertViewHas('overdue_payreqs', 1);
    }

    public function test_payreq_status_update_skips_closed_advance_and_logs_warning(): void
    {
        $user = User::factory()->create();
        $originalDueDate = '2026-08-01';

        $payreq = Payreq::query()->create([
            'user_id' => $user->id,
            'nomor' => 'ADV-CLOSE-1',
            'type' => 'advance',
            'status' => 'close',
            'amount' => 300000,
            'due_date' => $originalDueDate,
            'project' => '001H',
            'remarks' => 'Sudah close',
            'printable' => 0,
            'deletable' => 0,
        ]);

        $outgoing = new Outgoing([
            'payreq_id' => $payreq->id,
            'amount' => 300000,
            'outgoing_date' => '2026-10-01',
        ]);

        $message = app(CashierApprovedController::class)->payreqStatusUpdate($payreq, $outgoing);

        $payreq->refresh();

        $this->assertSame('close', $payreq->status);
        $this->assertSame($originalDueDate, Carbon::parse($payreq->due_date)->toDateString());
        $this->assertSame(
            'PR ADV-CLOSE-1 sudah selesai realisasinya; status tidak diubah menjadi Paid.',
            $message
        );
    }

    public function test_payreq_status_update_sets_paid_and_due_date_for_unrealized_advance(): void
    {
        $user = User::factory()->create();

        $payreq = Payreq::query()->create([
            'user_id' => $user->id,
            'nomor' => 'ADV-NEW-1',
            'type' => 'advance',
            'status' => 'approved',
            'amount' => 200000,
            'project' => '001H',
            'remarks' => 'Baru dibayar',
        ]);

        $outgoing = new Outgoing([
            'payreq_id' => $payreq->id,
            'amount' => 200000,
            'outgoing_date' => '2026-10-01',
        ]);

        $message = app(CashierApprovedController::class)->payreqStatusUpdate($payreq, $outgoing);

        $payreq->refresh();

        $this->assertNull($message);
        $this->assertSame('paid', $payreq->status);
        $this->assertSame('2026-10-08', Carbon::parse($payreq->due_date)->toDateString());
        $this->assertSame(0, (int) $payreq->printable);
    }

    public function test_payreq_status_update_still_closes_reimburse_payreq(): void
    {
        $user = User::factory()->create();

        $payreq = Payreq::query()->create([
            'user_id' => $user->id,
            'nomor' => 'REIM-1',
            'type' => 'reimburse',
            'status' => 'approved',
            'amount' => 150000,
            'project' => '001H',
            'remarks' => 'Reimburse',
        ]);

        $realization = Realization::query()->create([
            'nomor' => 'REAL-REIM-1',
            'payreq_id' => $payreq->id,
            'user_id' => $user->id,
            'project' => '001H',
            'status' => 'approved',
        ]);

        $outgoing = new Outgoing([
            'payreq_id' => $payreq->id,
            'amount' => 150000,
            'outgoing_date' => '2026-10-01',
        ]);

        $message = app(CashierApprovedController::class)->payreqStatusUpdate($payreq, $outgoing);

        $payreq->refresh();
        $realization->refresh();

        $this->assertNull($message);
        $this->assertSame('close', $payreq->status);
        $this->assertSame('reimburse-paid', $realization->status);
    }

    public function test_paid_advance_with_posted_verification_journal_is_not_overdue(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-01 12:00:00'));

        $user = User::factory()->create();

        $vj = VerificationJournal::query()->create([
            'nomor' => '26VJ00102702',
            'date' => '2026-09-29',
            'project' => '001H',
            'sap_journal_no' => '267692549',
            'amount' => 1100000,
            'status' => 'posted',
        ]);

        $payreq = Payreq::query()->create([
            'user_id' => $user->id,
            'nomor' => 'ADV-VJ-1',
            'type' => 'advance',
            'status' => 'paid',
            'amount' => 800000,
            'due_date' => '2026-09-20',
            'project' => '001H',
            'remarks' => 'VJ posted',
        ]);

        Realization::query()->create([
            'nomor' => 'REAL-VJ-1',
            'payreq_id' => $payreq->id,
            'user_id' => $user->id,
            'project' => '001H',
            'status' => 'approved',
            'verification_journal_id' => $vj->id,
        ]);

        $this->actingAs($user)
            ->get(route('user-payreqs.index'))
            ->assertViewHas('overdue_payreqs', 0)
            ->assertViewHas('enable_payreq', true);
    }
}
