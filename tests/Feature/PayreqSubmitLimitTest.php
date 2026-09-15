<?php

namespace Tests\Feature;

use App\Models\Anggaran;
use App\Models\ApiKey;
use App\Models\ApprovalStage;
use App\Models\Parameter;
use App\Models\Payreq;
use App\Models\Realization;
use App\Models\RealizationDetail;
use App\Models\User;
use App\Services\PayreqSubmitLimitService;
use App\Support\PayreqBudgetLinkMode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayreqSubmitLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_advance_submit_allowed_when_below_limit(): void
    {
        $user = $this->makeUser('022C');
        $approver = User::factory()->create(['project' => '022C', 'department_id' => $user->department_id]);
        $this->createApprovalStage($user, $approver);
        $anggaran = $this->makeApprovedAnggaran($user);

        $this->createSubmittedPayreqs($user, 4);

        $draft = $this->makeAdvancePayreq($user, ['rab_id' => $anggaran->id]);

        $response = $this->actingAs($user)->post(route('user-payreqs.advance.proses'), [
            'button_type' => 'edit_submit',
            'payreq_id' => $draft->id,
            'employee_id' => $user->id,
            'payreq_type' => 'advance',
            'payreq_no' => $draft->nomor,
            'project' => '022C',
            'department_id' => $user->department_id,
            'remarks' => 'Submit ke lima',
            'amount' => '1000',
            'budget_link_mode' => PayreqBudgetLinkMode::LEGACY,
            'rab_id' => $anggaran->id,
        ]);

        $response->assertRedirect(route('user-payreqs.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('payreqs', [
            'id' => $draft->id,
            'status' => 'submitted',
        ]);
        $this->assertSame(5, app(PayreqSubmitLimitService::class)->submittedCount($user->id));
    }

    public function test_advance_submit_blocked_at_limit_and_stays_draft(): void
    {
        $user = $this->makeUser('022C');
        $anggaran = $this->makeApprovedAnggaran($user);

        $this->createSubmittedPayreqs($user, 5);

        $draft = $this->makeAdvancePayreq($user, ['rab_id' => $anggaran->id]);

        $response = $this->actingAs($user)->post(route('user-payreqs.advance.proses'), [
            'button_type' => 'edit_submit',
            'payreq_id' => $draft->id,
            'employee_id' => $user->id,
            'payreq_type' => 'advance',
            'payreq_no' => $draft->nomor,
            'project' => '022C',
            'department_id' => $user->department_id,
            'remarks' => 'Harus ditolak',
            'amount' => '1000',
            'budget_link_mode' => PayreqBudgetLinkMode::LEGACY,
            'rab_id' => $anggaran->id,
        ]);

        $response->assertRedirect(route('user-payreqs.index'));
        $response->assertSessionHas('error', 'Masih ada 5 payreq kamu yang menunggu approval (maksimal 5). Selesaikan dulu sebelum submit payreq baru.');
        $this->assertDatabaseHas('payreqs', [
            'id' => $draft->id,
            'status' => 'draft',
        ]);
    }

    public function test_reimburse_submit_blocked_when_combined_submitted_reaches_limit(): void
    {
        $user = $this->makeUser('022C');
        $anggaran = $this->makeApprovedAnggaran($user);

        $this->createSubmittedPayreqs($user, 3, 'advance');
        $this->createSubmittedPayreqs($user, 2, 'reimburse');

        [$payreq, $realization] = $this->makeReimburseDraft($user, $anggaran);

        $response = $this->actingAs($user)->post(route('user-payreqs.reimburse.submit_payreq'), [
            'realization_id' => $realization->id,
        ]);

        $response->assertRedirect(route('user-payreqs.index'));
        $response->assertSessionHas('error', 'Masih ada 5 payreq kamu yang menunggu approval (maksimal 5). Selesaikan dulu sebelum submit payreq baru.');
        $this->assertDatabaseHas('payreqs', [
            'id' => $payreq->id,
            'status' => 'draft',
        ]);
    }

    public function test_draft_and_revise_statuses_are_not_counted(): void
    {
        $user = $this->makeUser('022C');
        $approver = User::factory()->create(['project' => '022C', 'department_id' => $user->department_id]);
        $this->createApprovalStage($user, $approver);
        $anggaran = $this->makeApprovedAnggaran($user);

        for ($i = 0; $i < 5; $i++) {
            $this->makeAdvancePayreq($user, ['status' => 'draft']);
        }
        $this->makeAdvancePayreq($user, ['status' => 'revise']);

        $draft = $this->makeAdvancePayreq($user, ['rab_id' => $anggaran->id]);

        $response = $this->actingAs($user)->post(route('user-payreqs.advance.proses'), [
            'button_type' => 'edit_submit',
            'payreq_id' => $draft->id,
            'employee_id' => $user->id,
            'payreq_type' => 'advance',
            'payreq_no' => $draft->nomor,
            'project' => '022C',
            'department_id' => $user->department_id,
            'remarks' => 'Draft tidak dihitung',
            'amount' => '1000',
            'budget_link_mode' => PayreqBudgetLinkMode::LEGACY,
            'rab_id' => $anggaran->id,
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('payreqs', [
            'id' => $draft->id,
            'status' => 'submitted',
        ]);
    }

    public function test_parameter_override_changes_limit(): void
    {
        Parameter::query()->where('name1', 'max_submitted_payreq')->update(['param_value' => '2']);

        $user = $this->makeUser('022C');
        $anggaran = $this->makeApprovedAnggaran($user);
        $approver = User::factory()->create(['project' => '022C', 'department_id' => $user->department_id]);
        $this->createApprovalStage($user, $approver);

        $this->createSubmittedPayreqs($user, 1);
        $allowedDraft = $this->makeAdvancePayreq($user, ['rab_id' => $anggaran->id]);

        $allowedResponse = $this->actingAs($user)->post(route('user-payreqs.advance.proses'), [
            'button_type' => 'edit_submit',
            'payreq_id' => $allowedDraft->id,
            'employee_id' => $user->id,
            'payreq_type' => 'advance',
            'payreq_no' => $allowedDraft->nomor,
            'project' => '022C',
            'department_id' => $user->department_id,
            'remarks' => 'Masih boleh',
            'amount' => '1000',
            'budget_link_mode' => PayreqBudgetLinkMode::LEGACY,
            'rab_id' => $anggaran->id,
        ]);

        $allowedResponse->assertSessionHas('success');

        $blockedDraft = $this->makeAdvancePayreq($user, ['rab_id' => $anggaran->id]);

        $blockedResponse = $this->actingAs($user)->post(route('user-payreqs.advance.proses'), [
            'button_type' => 'edit_submit',
            'payreq_id' => $blockedDraft->id,
            'employee_id' => $user->id,
            'payreq_type' => 'advance',
            'payreq_no' => $blockedDraft->nomor,
            'project' => '022C',
            'department_id' => $user->department_id,
            'remarks' => 'Sudah penuh',
            'amount' => '1000',
            'budget_link_mode' => PayreqBudgetLinkMode::LEGACY,
            'rab_id' => $anggaran->id,
        ]);

        $blockedResponse->assertSessionHas('error', 'Masih ada 2 payreq kamu yang menunggu approval (maksimal 2). Selesaikan dulu sebelum submit payreq baru.');
        $this->assertDatabaseHas('payreqs', [
            'id' => $blockedDraft->id,
            'status' => 'draft',
        ]);
    }

    public function test_default_limit_when_parameter_missing(): void
    {
        Parameter::query()->where('name1', 'max_submitted_payreq')->delete();

        $user = $this->makeUser('022C');
        $anggaran = $this->makeApprovedAnggaran($user);
        $approver = User::factory()->create(['project' => '022C', 'department_id' => $user->department_id]);
        $this->createApprovalStage($user, $approver);

        $this->assertSame(5, app(PayreqSubmitLimitService::class)->limit());

        $this->createSubmittedPayreqs($user, 4);
        $allowedDraft = $this->makeAdvancePayreq($user, ['rab_id' => $anggaran->id]);

        $allowedResponse = $this->actingAs($user)->post(route('user-payreqs.advance.proses'), [
            'button_type' => 'edit_submit',
            'payreq_id' => $allowedDraft->id,
            'employee_id' => $user->id,
            'payreq_type' => 'advance',
            'payreq_no' => $allowedDraft->nomor,
            'project' => '022C',
            'department_id' => $user->department_id,
            'remarks' => 'Ke lima',
            'amount' => '1000',
            'budget_link_mode' => PayreqBudgetLinkMode::LEGACY,
            'rab_id' => $anggaran->id,
        ]);

        $allowedResponse->assertSessionHas('success');
        $this->assertSame(5, app(PayreqSubmitLimitService::class)->submittedCount($user->id));

        $blockedDraft = $this->makeAdvancePayreq($user, ['rab_id' => $anggaran->id]);

        $blockedResponse = $this->actingAs($user)->post(route('user-payreqs.advance.proses'), [
            'button_type' => 'edit_submit',
            'payreq_id' => $blockedDraft->id,
            'employee_id' => $user->id,
            'payreq_type' => 'advance',
            'payreq_no' => $blockedDraft->nomor,
            'project' => '022C',
            'department_id' => $user->department_id,
            'remarks' => 'Ke enam ditolak',
            'amount' => '1000',
            'budget_link_mode' => PayreqBudgetLinkMode::LEGACY,
            'rab_id' => $anggaran->id,
        ]);

        $blockedResponse->assertSessionHas('error', 'Masih ada 5 payreq kamu yang menunggu approval (maksimal 5). Selesaikan dulu sebelum submit payreq baru.');
    }

    public function test_api_advance_submit_blocked_at_limit(): void
    {
        $user = $this->makeUser('022C');
        $anggaran = $this->makeApprovedAnggaran($user);
        $this->createSubmittedPayreqs($user, 5);

        ['raw_key' => $apiKey] = ApiKey::generate('test', 'payreq-test', null, $user->id);

        $response = $this->postJson('/api/payreqs/advance', [
            'employee_id' => $user->id,
            'remarks' => 'API submit ditolak',
            'amount' => 1000,
            'rab_id' => $anggaran->id,
            'submit' => true,
        ], [
            'X-API-Key' => $apiKey,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'Masih ada 5 payreq kamu yang menunggu approval (maksimal 5). Selesaikan dulu sebelum submit payreq baru.',
        ]);
        $this->assertDatabaseMissing('payreqs', [
            'user_id' => $user->id,
            'remarks' => 'API submit ditolak',
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
            'editable' => '1',
            'deletable' => '1',
        ], $overrides));
    }

    private function createSubmittedPayreqs(User $user, int $count, string $type = 'advance'): void
    {
        for ($i = 0; $i < $count; $i++) {
            Payreq::query()->create([
                'user_id' => $user->id,
                'nomor' => strtoupper($type).'-SUB-'.fake()->unique()->numerify('####'),
                'type' => $type,
                'status' => 'submitted',
                'amount' => 1000,
                'project' => $user->project,
                'department_id' => $user->department_id,
                'budget_link_mode' => PayreqBudgetLinkMode::LEGACY,
                'editable' => '0',
                'deletable' => '0',
                'submit_at' => now(),
            ]);
        }
    }

    private function createApprovalStage(User $user, User $approver): void
    {
        ApprovalStage::query()->create([
            'project' => $user->project,
            'department_id' => (string) $user->department_id,
            'approver_id' => (string) $approver->id,
            'document_type' => 'payreq',
        ]);
    }

    /**
     * @return array{0: Payreq, 1: Realization}
     */
    private function makeReimburseDraft(User $user, Anggaran $anggaran): array
    {
        $payreq = Payreq::query()->create([
            'user_id' => $user->id,
            'nomor' => 'RMB-'.fake()->unique()->numerify('####'),
            'type' => 'reimburse',
            'status' => 'draft',
            'amount' => 1000,
            'project' => $user->project,
            'department_id' => $user->department_id,
            'rab_id' => $anggaran->id,
            'editable' => '1',
            'deletable' => '1',
        ]);

        $realization = Realization::query()->create([
            'payreq_id' => $payreq->id,
            'project' => $user->project,
            'department_id' => $user->department_id,
            'user_id' => $user->id,
            'nomor' => 'RLZ-'.fake()->unique()->numerify('####'),
            'status' => 'reimburse-draft',
            'editable' => '1',
            'deletable' => '1',
        ]);

        RealizationDetail::query()->create([
            'realization_id' => $realization->id,
            'description' => 'Biaya reimburse',
            'amount' => 1000,
            'project' => $user->project,
            'department_id' => $user->department_id,
        ]);

        return [$payreq, $realization];
    }
}
