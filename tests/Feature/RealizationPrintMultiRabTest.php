<?php

namespace Tests\Feature;

use App\Models\Anggaran;
use App\Models\Payreq;
use App\Models\PayreqAnggaranAllocation;
use App\Models\Realization;
use App\Models\RealizationDetail;
use App\Models\User;
use App\Support\PayreqBudgetLinkMode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RealizationPrintMultiRabTest extends TestCase
{
    use RefreshDatabase;

    public function test_multi_rab_print_does_not_show_advance_allocations_block(): void
    {
        $user = $this->makeUser('000H');
        $anggaran = $this->makeApprovedAnggaran($user);
        $realization = $this->makeMultiRabRealization($user, $anggaran);

        $response = $this->actingAs($user)->get(route('user-payreqs.realizations.print', $realization->id));

        $response->assertOk();
        $response->assertDontSee('Advance allocations', false);
        $response->assertDontSee('thead-light', false);
        $response->assertDontSee('RAB No.', false);
    }

    public function test_multi_rab_print_still_shows_anggaran_column_in_detail_table(): void
    {
        $user = $this->makeUser('000H');
        $anggaran = $this->makeApprovedAnggaran($user);
        $realization = $this->makeMultiRabRealization($user, $anggaran);

        $response = $this->actingAs($user)->get(route('user-payreqs.realizations.print', $realization->id));

        $response->assertOk();
        $response->assertSee('Anggaran', false);
        $response->assertSee('No. '.$anggaran->nomor, false);
    }

    public function test_legacy_realization_print_shows_rab_no_row(): void
    {
        $user = $this->makeUser('022C');
        $anggaran = $this->makeApprovedAnggaran($user);
        $realization = $this->makeLegacyRealization($user, $anggaran);

        $response = $this->actingAs($user)->get(route('user-payreqs.realizations.print', $realization->id));

        $response->assertOk();
        $response->assertSee('RAB No.', false);
        $response->assertSee('No. '.$anggaran->nomor, false);
        $response->assertDontSee('Advance allocations', false);
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

    private function makeMultiRabRealization(User $user, Anggaran $anggaran): Realization
    {
        $payreq = Payreq::query()->create([
            'user_id' => $user->id,
            'nomor' => 'ADV-MULTI-'.fake()->unique()->numerify('####'),
            'type' => 'advance',
            'status' => 'paid',
            'amount' => 1000,
            'project' => $user->project,
            'department_id' => $user->department_id,
            'budget_link_mode' => PayreqBudgetLinkMode::MULTI_ALLOCATION,
            'rab_id' => $anggaran->id,
            'approved_at' => now(),
            'remarks' => 'Multi RAB advance',
        ]);

        PayreqAnggaranAllocation::create([
            'payreq_id' => $payreq->id,
            'anggaran_id' => $anggaran->id,
            'amount' => 1000,
            'sort_order' => 0,
            'remarks' => 'Allocation row',
        ]);

        $realization = Realization::query()->create([
            'nomor' => 'REAL-MULTI-'.fake()->unique()->numerify('####'),
            'payreq_id' => $payreq->id,
            'user_id' => $user->id,
            'project' => $user->project,
            'department_id' => $user->department_id,
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        RealizationDetail::query()->create([
            'realization_id' => $realization->id,
            'project' => $user->project,
            'department_id' => $user->department_id,
            'description' => 'Expense line',
            'amount' => 1000,
            'rab_id' => $anggaran->id,
            'expense_date' => now()->toDateString(),
        ]);

        return $realization;
    }

    private function makeLegacyRealization(User $user, Anggaran $anggaran): Realization
    {
        $payreq = Payreq::query()->create([
            'user_id' => $user->id,
            'nomor' => 'ADV-LEGACY-'.fake()->unique()->numerify('####'),
            'type' => 'advance',
            'status' => 'paid',
            'amount' => 1000,
            'project' => $user->project,
            'department_id' => $user->department_id,
            'budget_link_mode' => PayreqBudgetLinkMode::LEGACY,
            'rab_id' => $anggaran->id,
            'approved_at' => now(),
            'remarks' => 'Legacy advance',
        ]);

        $realization = Realization::query()->create([
            'nomor' => 'REAL-LEGACY-'.fake()->unique()->numerify('####'),
            'payreq_id' => $payreq->id,
            'user_id' => $user->id,
            'project' => $user->project,
            'department_id' => $user->department_id,
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        RealizationDetail::query()->create([
            'realization_id' => $realization->id,
            'project' => $user->project,
            'department_id' => $user->department_id,
            'description' => 'Expense line',
            'amount' => 1000,
            'expense_date' => now()->toDateString(),
        ]);

        return $realization;
    }
}
