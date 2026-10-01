<?php

namespace Tests\Feature;

use App\Models\Outgoing;
use App\Models\Payreq;
use App\Models\User;
use App\Models\VerificationJournal;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReportsOngoingOutstandingAdvanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'superadmin', 'cashier'] as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }
    }

    public function test_reports_ongoing_data_excludes_paid_advance_with_finished_realization(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-01 12:00:00'));

        $user = User::factory()->create(['project' => '000H']);
        $user->assignRole('admin');

        $finished = Payreq::query()->create([
            'user_id' => $user->id,
            'nomor' => 'ADV-RPT-DONE',
            'type' => 'advance',
            'status' => 'paid',
            'amount' => 500000,
            'due_date' => '2026-09-10',
            'project' => '000H',
            'remarks' => 'Selesai',
        ]);
        $finished->realization()->create([
            'nomor' => 'REAL-RPT-DONE',
            'user_id' => $user->id,
            'project' => '000H',
            'status' => 'close',
        ]);
        Outgoing::query()->create([
            'payreq_id' => $finished->id,
            'amount' => 500000,
            'outgoing_date' => '2026-09-01',
        ]);

        $outstanding = Payreq::query()->create([
            'user_id' => $user->id,
            'nomor' => 'ADV-RPT-OPEN',
            'type' => 'advance',
            'status' => 'paid',
            'amount' => 300000,
            'due_date' => '2026-09-15',
            'project' => '000H',
            'remarks' => 'Belum realisasi',
        ]);
        Outgoing::query()->create([
            'payreq_id' => $outstanding->id,
            'amount' => 300000,
            'outgoing_date' => '2026-09-05',
        ]);

        $reimburse = Payreq::query()->create([
            'user_id' => $user->id,
            'nomor' => 'REIM-RPT-1',
            'type' => 'reimburse',
            'status' => 'paid',
            'amount' => 100000,
            'project' => '000H',
            'remarks' => 'Reimburse',
        ]);
        Outgoing::query()->create([
            'payreq_id' => $reimburse->id,
            'amount' => 100000,
            'outgoing_date' => '2026-09-01',
        ]);

        $response = $this->actingAs($user)
            ->getJson(route('reports.ongoing.data'));

        $response->assertOk();

        $nomors = collect($response->json('data'))->pluck('nomor')->map(fn ($html) => strip_tags($html))->all();

        $this->assertContains('ADV-RPT-OPEN', $nomors);
        $this->assertNotContains('ADV-RPT-DONE', $nomors);
        $this->assertNotContains('REIM-RPT-1', $nomors);

        Carbon::setTestNow();
    }

    public function test_reports_ongoing_includes_advance_with_realization_status_and_open_realization(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-01 12:00:00'));

        $user = User::factory()->create(['project' => '000H']);
        $user->assignRole('admin');

        $inRealization = Payreq::query()->create([
            'user_id' => $user->id,
            'nomor' => 'ADV-RPT-REALIZING',
            'type' => 'advance',
            'status' => 'realization',
            'amount' => 450000,
            'due_date' => '2026-09-20',
            'project' => '000H',
            'remarks' => 'Realisasi diajukan',
        ]);
        $inRealization->realization()->create([
            'nomor' => 'REAL-RPT-OPEN',
            'user_id' => $user->id,
            'project' => '000H',
            'status' => 'approved',
        ]);
        Outgoing::query()->create([
            'payreq_id' => $inRealization->id,
            'amount' => 450000,
            'outgoing_date' => '2026-09-01',
        ]);

        $response = $this->actingAs($user)
            ->getJson(route('reports.ongoing.data'));

        $response->assertOk();

        $nomors = collect($response->json('data'))->pluck('nomor')->map(fn ($html) => strip_tags($html))->all();

        $this->assertContains('ADV-RPT-REALIZING', $nomors);

        Carbon::setTestNow();
    }

    public function test_reports_ongoing_excludes_realization_status_advance_when_realization_finished(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-01 12:00:00'));

        $user = User::factory()->create(['project' => '000H']);
        $user->assignRole('admin');

        $finishedWhileRealizationStatus = Payreq::query()->create([
            'user_id' => $user->id,
            'nomor' => 'ADV-RPT-REAL-CLOSE',
            'type' => 'advance',
            'status' => 'realization',
            'amount' => 200000,
            'due_date' => '2026-09-10',
            'project' => '000H',
            'remarks' => 'Realisasi selesai',
        ]);
        $finishedWhileRealizationStatus->realization()->create([
            'nomor' => 'REAL-RPT-CLOSE',
            'user_id' => $user->id,
            'project' => '000H',
            'status' => 'verification-complete',
        ]);
        Outgoing::query()->create([
            'payreq_id' => $finishedWhileRealizationStatus->id,
            'amount' => 200000,
            'outgoing_date' => '2026-09-01',
        ]);

        $response = $this->actingAs($user)
            ->getJson(route('reports.ongoing.data'));

        $response->assertOk();

        $nomors = collect($response->json('data'))->pluck('nomor')->map(fn ($html) => strip_tags($html))->all();

        $this->assertNotContains('ADV-RPT-REAL-CLOSE', $nomors);

        Carbon::setTestNow();
    }

    public function test_reports_ongoing_index_total_amount_matches_data_rows(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-01 12:00:00'));

        $user = User::factory()->create(['project' => '000H']);
        $user->assignRole('admin');

        $sumA = Payreq::query()->create([
            'user_id' => $user->id,
            'nomor' => 'ADV-RPT-SUM-A',
            'type' => 'advance',
            'status' => 'paid',
            'amount' => 100000,
            'due_date' => '2026-09-15',
            'project' => '000H',
            'remarks' => 'Open paid',
        ]);
        Outgoing::query()->create([
            'payreq_id' => $sumA->id,
            'amount' => 100000,
            'outgoing_date' => '2026-09-05',
        ]);

        $sumB = Payreq::query()->create([
            'user_id' => $user->id,
            'nomor' => 'ADV-RPT-SUM-B',
            'type' => 'advance',
            'status' => 'realization',
            'amount' => 250000,
            'due_date' => '2026-09-20',
            'project' => '000H',
            'remarks' => 'Open realization',
        ]);
        $sumB->realization()->create([
            'nomor' => 'REAL-SUM-B',
            'user_id' => $user->id,
            'project' => '000H',
            'status' => 'submitted',
        ]);
        Outgoing::query()->create([
            'payreq_id' => $sumB->id,
            'amount' => 250000,
            'outgoing_date' => '2026-09-06',
        ]);

        $sumDone = Payreq::query()->create([
            'user_id' => $user->id,
            'nomor' => 'ADV-RPT-SUM-DONE',
            'type' => 'advance',
            'status' => 'paid',
            'amount' => 999999,
            'due_date' => '2026-09-10',
            'project' => '000H',
            'remarks' => 'Finished',
        ]);
        $sumDone->realization()->create([
            'nomor' => 'REAL-SUM-DONE',
            'user_id' => $user->id,
            'project' => '000H',
            'status' => 'close',
        ]);
        Outgoing::query()->create([
            'payreq_id' => $sumDone->id,
            'amount' => 999999,
            'outgoing_date' => '2026-09-01',
        ]);

        $indexResponse = $this->actingAs($user)->get(route('reports.ongoing.index'));
        $indexResponse->assertOk();
        $totalAmount = $indexResponse->viewData('total_amount');

        $dataResponse = $this->actingAs($user)->getJson(route('reports.ongoing.data'));
        $dataResponse->assertOk();
        $rows = collect($dataResponse->json('data'));
        $rowSum = $rows->sum(fn (array $row) => (int) str_replace('.', '', $row['amount']));

        $this->assertSame(350000, (int) $totalAmount);
        $this->assertSame(350000, $rowSum);
        $this->assertCount(2, $rows);

        Carbon::setTestNow();
    }

    public function test_reports_ongoing_excludes_paid_advance_with_posted_verification_journal(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-01 12:00:00'));

        $user = User::factory()->create(['project' => '000H']);
        $user->assignRole('admin');

        $vj = VerificationJournal::query()->create([
            'nomor' => '26VJ-RPT-001',
            'date' => '2026-09-29',
            'project' => '000H',
            'sap_journal_no' => '267692549',
            'amount' => 1100000,
            'status' => 'posted',
        ]);

        $posted = Payreq::query()->create([
            'user_id' => $user->id,
            'nomor' => 'ADV-RPT-VJ',
            'type' => 'advance',
            'status' => 'paid',
            'amount' => 800000,
            'due_date' => '2026-09-20',
            'project' => '000H',
            'remarks' => 'VJ posted',
        ]);
        $posted->realization()->create([
            'nomor' => 'REAL-RPT-VJ',
            'user_id' => $user->id,
            'project' => '000H',
            'status' => 'approved',
            'verification_journal_id' => $vj->id,
        ]);

        $response = $this->actingAs($user)->getJson(route('reports.ongoing.data'));
        $response->assertOk();

        $nomors = collect($response->json('data'))->pluck('nomor')->map(fn ($html) => strip_tags($html))->all();
        $this->assertNotContains('ADV-RPT-VJ', $nomors);

        Carbon::setTestNow();
    }
}
