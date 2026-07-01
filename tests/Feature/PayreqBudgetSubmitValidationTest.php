<?php

namespace Tests\Feature;

use App\Models\Anggaran;
use App\Models\Payreq;
use App\Models\PayreqAnggaranAllocation;
use App\Models\User;
use App\Services\PayreqBudgetSubmitValidator;
use App\Support\PayreqBudgetLinkMode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayreqBudgetSubmitValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_advance_submit_rejected_without_rab_for_site_project(): void
    {
        $user = $this->makeUser('022C');
        $payreq = $this->makeAdvancePayreq($user, [
            'project' => '022C',
            'rab_id' => null,
        ]);

        $error = app(PayreqBudgetSubmitValidator::class)->validate($payreq);

        $this->assertSame('RAB harus diisi, payreq belum bisa disubmit', $error);
    }

    public function test_legacy_advance_submit_passes_with_valid_rab(): void
    {
        $user = $this->makeUser('022C');
        $anggaran = $this->makeApprovedAnggaran($user);
        $payreq = $this->makeAdvancePayreq($user, [
            'project' => '022C',
            'rab_id' => $anggaran->id,
        ]);

        $error = app(PayreqBudgetSubmitValidator::class)->validate($payreq);

        $this->assertNull($error);
    }

    public function test_legacy_advance_submit_rejects_inactive_anggaran(): void
    {
        $user = $this->makeUser('017C');
        $anggaran = $this->makeApprovedAnggaran($user, ['is_active' => 0]);
        $payreq = $this->makeAdvancePayreq($user, [
            'project' => '017C',
            'rab_id' => $anggaran->id,
        ]);

        $error = app(PayreqBudgetSubmitValidator::class)->validate($payreq);

        $this->assertSame('RAB tidak valid atau tidak aktif', $error);
    }

    public function test_multi_allocation_submit_requires_at_least_one_row(): void
    {
        $user = $this->makeUser('000H');
        $payreq = $this->makeAdvancePayreq($user, [
            'project' => '000H',
            'budget_link_mode' => PayreqBudgetLinkMode::MULTI_ALLOCATION,
            'rab_id' => null,
            'amount' => 1000,
        ]);

        $error = app(PayreqBudgetSubmitValidator::class)->validate($payreq);

        $this->assertSame('Alokasi anggaran minimal satu baris, payreq belum bisa disubmit', $error);
    }

    public function test_multi_allocation_submit_requires_sum_match(): void
    {
        $user = $this->makeUser('000H');
        $anggaran = $this->makeApprovedAnggaran($user);
        $payreq = $this->makeAdvancePayreq($user, [
            'project' => '000H',
            'budget_link_mode' => PayreqBudgetLinkMode::MULTI_ALLOCATION,
            'rab_id' => $anggaran->id,
            'amount' => 1000,
        ]);

        PayreqAnggaranAllocation::create([
            'payreq_id' => $payreq->id,
            'anggaran_id' => $anggaran->id,
            'amount' => 500,
            'sort_order' => 0,
        ]);

        $payreq->load('anggaranAllocations');

        $error = app(PayreqBudgetSubmitValidator::class)->validate($payreq);

        $this->assertSame('Jumlah alokasi baris tidak sama dengan total payreq', $error);
    }

    public function test_other_payreq_type_skips_budget_validation(): void
    {
        $user = $this->makeUser('022C');
        $payreq = Payreq::query()->create([
            'user_id' => $user->id,
            'nomor' => 'OTHER-1',
            'type' => 'other',
            'status' => 'draft',
            'amount' => 1000,
            'project' => '022C',
            'department_id' => $user->department_id,
            'rab_id' => null,
        ]);

        $error = app(PayreqBudgetSubmitValidator::class)->validate($payreq);

        $this->assertNull($error);
    }

    public function test_advance_proses_rejects_submit_without_rab(): void
    {
        $user = $this->makeUser('022C');

        $response = $this->actingAs($user)->post(route('user-payreqs.advance.proses'), [
            'button_type' => 'create_submit',
            'employee_id' => $user->id,
            'payreq_type' => 'advance',
            'payreq_no' => 'DRAFT-022C-001',
            'project' => '022C',
            'department_id' => $user->department_id,
            'remarks' => 'Test advance without RAB',
            'amount' => '1000',
            'budget_link_mode' => PayreqBudgetLinkMode::LEGACY,
        ]);

        $response->assertSessionHasErrors('rab_id');
        $this->assertDatabaseMissing('payreqs', [
            'user_id' => $user->id,
            'remarks' => 'Test advance without RAB',
            'status' => 'submitted',
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeUser(string $project, array $overrides = []): User
    {
        $departmentId = \DB::table('departments')->insertGetId([
            'department_name' => 'Test Dept',
            'akronim' => 'TD',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return User::factory()->create(array_merge([
            'project' => $project,
            'department_id' => $departmentId,
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeApprovedAnggaran(User $user, array $overrides = []): Anggaran
    {
        return Anggaran::query()->create(array_merge([
            'nomor' => 'TEST-RAB-'.fake()->unique()->numerify('####'),
            'description' => 'Test budget',
            'project' => $user->project,
            'rab_project' => $user->project,
            'department_id' => $user->department_id,
            'type' => 'event',
            'amount' => 1000000,
            'balance' => 0,
            'usage' => 'user',
            'status' => 'approved',
            'is_active' => 1,
            'created_by' => $user->id,
            'date' => now()->toDateString(),
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeAdvancePayreq(User $user, array $overrides = []): Payreq
    {
        return Payreq::query()->create(array_merge([
            'user_id' => $user->id,
            'nomor' => 'ADV-'.fake()->unique()->numerify('####'),
            'type' => 'advance',
            'status' => 'draft',
            'amount' => 1000,
            'project' => $user->project,
            'department_id' => $user->department_id,
            'budget_link_mode' => PayreqBudgetLinkMode::LEGACY,
            'rab_id' => null,
        ], $overrides));
    }
}
