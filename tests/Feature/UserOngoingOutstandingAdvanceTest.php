<?php

namespace Tests\Feature;

use App\Models\Outgoing;
use App\Models\Payreq;
use App\Models\Realization;
use App\Models\User;
use App\Models\VerificationJournal;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserOngoingOutstandingAdvanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'superadmin'] as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }
    }

    public function test_ongoing_data_excludes_paid_advance_with_finished_realization_for_owner(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-01 12:00:00'));

        $user = User::factory()->create();

        $finished = Payreq::query()->create([
            'user_id' => $user->id,
            'nomor' => 'ADV-ONGO-DONE',
            'type' => 'advance',
            'status' => 'paid',
            'amount' => 500000,
            'due_date' => '2026-09-10',
            'project' => '001H',
            'remarks' => 'Selesai',
        ]);
        $finished->realization()->create([
            'nomor' => 'REAL-ONGO-DONE',
            'user_id' => $user->id,
            'project' => '001H',
            'status' => 'close',
        ]);
        Outgoing::query()->create([
            'payreq_id' => $finished->id,
            'amount' => 500000,
            'outgoing_date' => '2026-09-01',
        ]);

        $outstanding = Payreq::query()->create([
            'user_id' => $user->id,
            'nomor' => 'ADV-ONGO-OPEN',
            'type' => 'advance',
            'status' => 'paid',
            'amount' => 300000,
            'due_date' => '2026-09-15',
            'project' => '001H',
            'remarks' => 'Belum realisasi',
        ]);
        Outgoing::query()->create([
            'payreq_id' => $outstanding->id,
            'amount' => 300000,
            'outgoing_date' => '2026-09-05',
        ]);

        $response = $this->actingAs($user)
            ->getJson(route('user-payreqs.ongoings.data'));

        $response->assertOk();

        $nomors = collect($response->json('data'))->pluck('nomor')->all();

        $this->assertContains('ADV-ONGO-OPEN', $nomors);
        $this->assertNotContains('ADV-ONGO-DONE', $nomors);

        Carbon::setTestNow();
    }

    public function test_ongoing_data_excludes_paid_advance_with_posted_vj_for_admin(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-01 12:00:00'));

        $owner = User::factory()->create();
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $vj = VerificationJournal::query()->create([
            'nomor' => '26VJ-ONGO-1',
            'date' => '2026-09-29',
            'project' => '001H',
            'sap_journal_no' => '267692549',
            'amount' => 800000,
            'status' => 'posted',
        ]);

        $finished = Payreq::query()->create([
            'user_id' => $owner->id,
            'nomor' => 'ADV-ONGO-VJ',
            'type' => 'advance',
            'status' => 'paid',
            'amount' => 800000,
            'due_date' => '2026-09-20',
            'project' => '001H',
            'remarks' => 'VJ posted',
        ]);
        Realization::query()->create([
            'nomor' => 'REAL-ONGO-VJ',
            'payreq_id' => $finished->id,
            'user_id' => $owner->id,
            'project' => '001H',
            'status' => 'approved',
            'verification_journal_id' => $vj->id,
        ]);
        Outgoing::query()->create([
            'payreq_id' => $finished->id,
            'amount' => 800000,
            'outgoing_date' => '2026-09-01',
        ]);

        $response = $this->actingAs($admin)
            ->getJson(route('user-payreqs.ongoings.data'));

        $response->assertOk();

        $nomors = collect($response->json('data'))->pluck('nomor')->all();

        $this->assertNotContains('ADV-ONGO-VJ', $nomors);

        Carbon::setTestNow();
    }

    public function test_ongoing_data_ignores_non_advance_and_unpaid_payreqs(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-01 12:00:00'));

        $user = User::factory()->create();

        $reimburse = Payreq::query()->create([
            'user_id' => $user->id,
            'nomor' => 'REIM-ONGO-1',
            'type' => 'reimburse',
            'status' => 'paid',
            'amount' => 100000,
            'project' => '001H',
            'remarks' => 'Reimburse paid',
        ]);
        Outgoing::query()->create([
            'payreq_id' => $reimburse->id,
            'amount' => 100000,
            'outgoing_date' => '2026-09-01',
        ]);

        $approvedAdvance = Payreq::query()->create([
            'user_id' => $user->id,
            'nomor' => 'ADV-NOT-PAID',
            'type' => 'advance',
            'status' => 'approved',
            'amount' => 200000,
            'project' => '001H',
            'remarks' => 'Belum dibayar',
        ]);

        $paidOutstanding = Payreq::query()->create([
            'user_id' => $user->id,
            'nomor' => 'ADV-STILL-OPEN',
            'type' => 'advance',
            'status' => 'paid',
            'amount' => 250000,
            'due_date' => '2026-10-15',
            'project' => '001H',
            'remarks' => 'Masih outstanding',
        ]);
        Outgoing::query()->create([
            'payreq_id' => $paidOutstanding->id,
            'amount' => 250000,
            'outgoing_date' => '2026-09-20',
        ]);

        $response = $this->actingAs($user)
            ->getJson(route('user-payreqs.ongoings.data'));

        $response->assertOk();

        $nomors = collect($response->json('data'))->pluck('nomor')->all();

        $this->assertSame(['ADV-STILL-OPEN'], $nomors);
        $this->assertNotContains('REIM-ONGO-1', $nomors);
        $this->assertNotContains('ADV-NOT-PAID', $nomors);

        Carbon::setTestNow();
    }
}
