<?php

namespace Tests\Feature;

use App\Models\Outgoing;
use App\Models\Payreq;
use App\Models\User;
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
}
