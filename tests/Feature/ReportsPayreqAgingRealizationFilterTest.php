<?php

namespace Tests\Feature;

use App\Models\Outgoing;
use App\Models\Payreq;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReportsPayreqAgingRealizationFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'superadmin', 'cashier'] as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }
    }

    public function test_payreq_aging_includes_open_realization_status_advance(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-01 12:00:00'));

        $user = User::factory()->create(['project' => '000H']);
        $user->assignRole('admin');

        $payreq = Payreq::query()->create([
            'user_id' => $user->id,
            'nomor' => 'ADV-AGING-OPEN',
            'type' => 'advance',
            'status' => 'realization',
            'amount' => 400000,
            'project' => '000H',
            'remarks' => 'Masih proses',
        ]);
        $payreq->realization()->create([
            'nomor' => 'REAL-AGING-OPEN',
            'user_id' => $user->id,
            'project' => '000H',
            'status' => 'approved',
        ]);
        Outgoing::query()->create([
            'payreq_id' => $payreq->id,
            'amount' => 400000,
            'outgoing_date' => '2026-09-01',
        ]);

        $response = $this->actingAs($user)
            ->getJson(route('reports.ongoing.payreq-aging.data'));

        $response->assertOk();

        $nomors = collect($response->json('data'))->pluck('nomor')->map(fn ($html) => strip_tags($html))->all();
        $this->assertContains('ADV-AGING-OPEN', $nomors);

        Carbon::setTestNow();
    }

    public function test_payreq_aging_excludes_advance_when_realization_is_finished(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-01 12:00:00'));

        $user = User::factory()->create(['project' => '000H']);
        $user->assignRole('admin');

        $payreq = Payreq::query()->create([
            'user_id' => $user->id,
            'nomor' => 'ADV-AGING-DONE',
            'type' => 'advance',
            'status' => 'realization',
            'amount' => 500000,
            'project' => '000H',
            'remarks' => 'Selesai',
        ]);
        $payreq->realization()->create([
            'nomor' => 'REAL-AGING-DONE',
            'user_id' => $user->id,
            'project' => '000H',
            'status' => 'close',
        ]);
        Outgoing::query()->create([
            'payreq_id' => $payreq->id,
            'amount' => 500000,
            'outgoing_date' => '2026-09-01',
        ]);

        $response = $this->actingAs($user)
            ->getJson(route('reports.ongoing.payreq-aging.data'));

        $response->assertOk();

        $nomors = collect($response->json('data'))->pluck('nomor')->map(fn ($html) => strip_tags($html))->all();
        $this->assertNotContains('ADV-AGING-DONE', $nomors);

        Carbon::setTestNow();
    }
}
