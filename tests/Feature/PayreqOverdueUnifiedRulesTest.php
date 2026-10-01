<?php

namespace Tests\Feature;

use App\Models\Outgoing;
use App\Models\OverdueExtension;
use App\Models\Payreq;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayreqOverdueUnifiedRulesTest extends TestCase
{
    use RefreshDatabase;

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
}
