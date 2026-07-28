<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VerificationJournal;
use App\Models\VerificationJournalDetail;
use App\Services\VjRejectionAlertService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class VjRejectionAlertTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'akses_sap_sync'], ['guard_name' => 'web']);
    }

    protected function createRejectedJournal(User $creator, array $overrides = []): VerificationJournal
    {
        $validator = User::factory()->create();

        return VerificationJournal::query()->create(array_merge([
            'nomor' => 'VJ'.uniqid(),
            'date' => now()->toDateString(),
            'project' => $creator->project ?? '022C',
            'amount' => 1000,
            'created_by' => $creator->id,
            'validation_status' => VerificationJournal::VALIDATION_REJECTED,
            'validated_at' => now(),
            'validated_by' => $validator->id,
            'rejection_reason' => 'Wrong cost center on line 1',
            'sap_submission_attempts' => 0,
        ], $overrides));
    }

    public function test_creator_sees_rejection_banner_on_dashboard(): void
    {
        $creator = User::factory()->create(['project' => '022C']);
        $journal = $this->createRejectedJournal($creator, [
            'nomor' => '26VJ02209999',
            'rejection_reason' => 'test reject 022',
        ]);

        $this->actingAs($creator)
            ->get(route('dashboard.index'))
            ->assertOk()
            ->assertSee('Verification Journal Rejected')
            ->assertSee('26VJ02209999')
            ->assertSee('test reject 022')
            ->assertSee('Review &amp; Fix', false)
            ->assertSee(route('accounting.sap-sync.edit_vjdetail_display', ['vj_id' => $journal->id]), false);
    }

    public function test_other_user_does_not_see_creator_rejection_banner(): void
    {
        $creator = User::factory()->create(['project' => '022C']);
        $otherUser = User::factory()->create(['project' => '022C']);

        $this->createRejectedJournal($creator, [
            'rejection_reason' => 'creator-only rejection reason',
        ]);

        $this->actingAs($otherUser)
            ->get(route('dashboard.index'))
            ->assertOk()
            ->assertDontSee('creator-only rejection reason')
            ->assertDontSee('Verification Journal Rejected');
    }

    public function test_service_returns_edit_vjdetail_url_for_non_bank_journal(): void
    {
        $creator = User::factory()->create(['project' => '022C']);
        $journal = $this->createRejectedJournal($creator, [
            'type' => 'realization',
        ]);

        $alerts = app(VjRejectionAlertService::class)->getAlertsFor($creator);

        $this->assertCount(1, $alerts);
        $this->assertSame(route('accounting.sap-sync.edit_vjdetail_display', ['vj_id' => $journal->id]), $alerts->first()['url']);
    }

    public function test_service_returns_bank_edit_url_for_bank_journal(): void
    {
        $creator = User::factory()->create(['project' => '022C']);
        $journal = $this->createRejectedJournal($creator, [
            'type' => 'bank',
            'status' => 'draft',
        ]);

        $alerts = app(VjRejectionAlertService::class)->getAlertsFor($creator);

        $this->assertCount(1, $alerts);
        $this->assertSame(route('cashier.bank-transactions.edit', $journal->id), $alerts->first()['url']);
    }

    public function test_alert_disappears_after_update_detail_resets_validation(): void
    {
        $creator = User::factory()->create(['project' => '022C']);
        $creator->givePermissionTo('akses_sap_sync');

        $journal = $this->createRejectedJournal($creator);
        $detail = VerificationJournalDetail::query()->create([
            'verification_journal_id' => $journal->id,
            'realization_date' => now()->toDateString(),
            'account_code' => '61202001',
            'debit_credit' => 'debit',
            'description' => 'Test expense line',
            'project' => '022C',
            'cost_center' => '200',
            'amount' => 1000,
        ]);

        $this->actingAs($creator)
            ->get(route('dashboard.index'))
            ->assertSee('Wrong cost center on line 1');

        $this->actingAs($creator)
            ->post(route('accounting.sap-sync.update_detail'), [
                'vj_detail_id' => $detail->id,
                'account_code' => '61202001',
                'project' => '022C',
                'cost_center' => '100',
                'description' => 'Corrected expense line',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $journal->refresh();
        $this->assertSame(VerificationJournal::VALIDATION_PENDING, $journal->validation_status);
        $this->assertNull($journal->rejection_reason);

        $this->actingAs($creator)
            ->get(route('dashboard.index'))
            ->assertOk()
            ->assertDontSee('Wrong cost center on line 1')
            ->assertDontSee('Verification Journal Rejected');
    }

    public function test_alert_disappears_after_bank_transaction_resubmit(): void
    {
        $creator = User::factory()->create(['project' => '022C']);

        $journal = $this->createRejectedJournal($creator, [
            'type' => 'bank',
            'status' => 'draft',
            'description' => 'Bank test transaction',
            'bank_account' => '11101006',
        ]);

        $this->actingAs($creator)
            ->get(route('dashboard.index'))
            ->assertSee('Wrong cost center on line 1');

        $this->actingAs($creator)
            ->post(route('cashier.bank-transactions.submit', $journal->id))
            ->assertRedirect();

        $journal->refresh();
        $this->assertSame(VerificationJournal::VALIDATION_PENDING, $journal->validation_status);
        $this->assertNull($journal->rejection_reason);
        $this->assertSame('submitted', $journal->status);

        $this->actingAs($creator)
            ->get(route('dashboard.index'))
            ->assertOk()
            ->assertDontSee('Wrong cost center on line 1')
            ->assertDontSee('Verification Journal Rejected');
    }
}
