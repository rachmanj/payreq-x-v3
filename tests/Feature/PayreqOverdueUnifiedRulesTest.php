<?php

namespace Tests\Feature;

use App\Models\Outgoing;
use App\Models\OverdueExtension;
use App\Models\Payreq;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PayreqOverdueUnifiedRulesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(
            ['name' => 'approve_overdue_extension'],
            ['guard_name' => 'web'],
        );
    }

    public function test_admin_overdue_payreq_data_excludes_advance_with_finished_realization(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-01 12:00:00'));

        $user = User::factory()->create();
        $viewer = User::factory()->create();

        $finished = Payreq::query()->create([
            'user_id' => $user->id,
            'nomor' => 'ADV-ADMIN-DONE',
            'type' => 'advance',
            'status' => 'paid',
            'amount' => 500000,
            'due_date' => '2026-09-10',
            'project' => '001H',
            'remarks' => 'Selesai',
        ]);
        $finished->realization()->create([
            'nomor' => 'REAL-ADMIN-DONE',
            'user_id' => $user->id,
            'project' => '001H',
            'status' => 'close',
        ]);
        Outgoing::query()->create([
            'payreq_id' => $finished->id,
            'amount' => 500000,
            'outgoing_date' => '2026-09-01',
        ]);

        $stillOverdue = Payreq::query()->create([
            'user_id' => $user->id,
            'nomor' => 'ADV-ADMIN-OD',
            'type' => 'advance',
            'status' => 'paid',
            'amount' => 300000,
            'due_date' => '2026-09-15',
            'project' => '001H',
            'remarks' => 'Belum realisasi',
        ]);
        Outgoing::query()->create([
            'payreq_id' => $stillOverdue->id,
            'amount' => 300000,
            'outgoing_date' => '2026-09-05',
        ]);

        $response = $this->actingAs($viewer)
            ->getJson(route('document-overdue.payreq.data'));

        $response->assertOk();

        $nomors = collect($response->json('data'))->pluck('nomor')->map(function ($html) {
            return strip_tags($html);
        })->all();

        $this->assertContains('ADV-ADMIN-OD', $nomors);
        $this->assertNotContains('ADV-ADMIN-DONE', $nomors);
    }

    public function test_extension_store_rejects_payreq_when_realization_is_finished(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-01 12:00:00'));

        $user = User::factory()->create();

        $payreq = Payreq::query()->create([
            'user_id' => $user->id,
            'nomor' => 'ADV-EXT-DONE',
            'type' => 'advance',
            'status' => 'paid',
            'amount' => 400000,
            'due_date' => '2026-09-20',
            'project' => '000H',
            'remarks' => 'Sudah lunas',
        ]);
        $payreq->realization()->create([
            'nomor' => 'REAL-EXT-DONE',
            'user_id' => $user->id,
            'project' => '000H',
            'status' => 'verification-complete',
        ]);

        $this->actingAs($user)
            ->from(route('user-payreqs.index'))
            ->post(route('document-overdue.extensions.store'), [
                'document_type' => OverdueExtension::DOCUMENT_PAYREQ,
                'document_id' => $payreq->id,
                'requested_due_date' => '2026-10-05',
                'reason' => 'Tidak perlu lagi',
            ])
            ->assertSessionHasErrors('document_id');

        $errors = session('errors')->get('document_id');
        $this->assertStringContainsString('ADV-EXT-DONE', $errors[0]);
        $this->assertStringContainsString('realisasinya sudah selesai', $errors[0]);

        $this->assertDatabaseCount('overdue_extensions', 0);
    }

    public function test_extension_store_still_accepts_truly_overdue_advance(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-01 12:00:00'));

        $user = User::factory()->create();

        $payreq = Payreq::query()->create([
            'user_id' => $user->id,
            'nomor' => 'ADV-EXT-OD',
            'type' => 'advance',
            'status' => 'paid',
            'amount' => 200000,
            'due_date' => '2026-09-20',
            'project' => '000H',
            'remarks' => 'Masih tunggu realisasi',
        ]);

        $this->actingAs($user)
            ->from(route('user-payreqs.index'))
            ->post(route('document-overdue.extensions.store'), [
                'document_type' => OverdueExtension::DOCUMENT_PAYREQ,
                'document_id' => $payreq->id,
                'requested_due_date' => '2026-10-05',
                'reason' => 'Butuh waktu tambahan',
            ])
            ->assertRedirect(route('user-payreqs.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('overdue_extensions', [
            'document_type' => OverdueExtension::DOCUMENT_PAYREQ,
            'document_id' => $payreq->id,
            'user_id' => $user->id,
            'status' => OverdueExtension::STATUS_PENDING,
        ]);
    }

    public function test_admin_direct_extend_rejects_finished_realization_with_indonesian_message(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-01 12:00:00'));

        $user = User::factory()->create();
        $user->givePermissionTo('approve_overdue_extension');

        $payreq = Payreq::query()->create([
            'user_id' => $user->id,
            'nomor' => '26010101009',
            'type' => 'advance',
            'status' => 'paid',
            'amount' => 400000,
            'due_date' => '2026-09-20',
            'project' => '000H',
            'remarks' => 'Sudah lunas',
        ]);
        $payreq->realization()->create([
            'nomor' => 'REAL-DIRECT-DONE',
            'user_id' => $user->id,
            'project' => '000H',
            'status' => 'close',
        ]);

        $this->actingAs($user)
            ->post(route('document-overdue.payreq.extend'), [
                'payreq_id' => $payreq->id,
                'new_due_date' => '2026-10-15',
            ])
            ->assertRedirect(route('document-overdue.payreq.index'))
            ->assertSessionHas('error');

        $error = session('error');
        $this->assertStringContainsString('26010101009', $error);
        $this->assertStringContainsString('realisasinya sudah selesai', $error);

        Carbon::setTestNow();
    }

    public function test_admin_direct_extend_rejects_not_yet_due_with_indonesian_message(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-01 12:00:00'));

        $user = User::factory()->create();
        $user->givePermissionTo('approve_overdue_extension');

        $payreq = Payreq::query()->create([
            'user_id' => $user->id,
            'nomor' => 'ADV-NOT-DUE',
            'type' => 'advance',
            'status' => 'paid',
            'amount' => 200000,
            'due_date' => '2026-10-15',
            'project' => '000H',
            'remarks' => 'Masih dalam tempo',
        ]);

        $this->actingAs($user)
            ->post(route('document-overdue.payreq.extend'), [
                'payreq_id' => $payreq->id,
                'new_due_date' => '2026-10-20',
            ])
            ->assertRedirect(route('document-overdue.payreq.index'))
            ->assertSessionHas('error');

        $error = session('error');
        $this->assertStringContainsString('ADV-NOT-DUE', $error);
        $this->assertStringContainsString('belum jatuh tempo', $error);

        Carbon::setTestNow();
    }
}
