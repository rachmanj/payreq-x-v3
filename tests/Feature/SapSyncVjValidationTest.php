<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VerificationJournal;
use App\Models\VerificationJournalDetail;
use App\Services\SapJournalSubmissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SapSyncVjValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'akses_sap_sync'], ['guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'edit_verification_project'], ['guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'validate_vj'], ['guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'validate_vj'], ['guard_name' => 'web']);

        foreach (['superadmin', 'admin', 'cashier', 'approver', 'cashier_022'] as $roleName) {
            Role::query()->firstOrCreate(['name' => $roleName], ['guard_name' => 'web']);
        }
    }

    protected function createCashierUser(): User
    {
        $user = User::factory()->create(['project' => '000H']);
        $user->assignRole('cashier');
        $user->givePermissionTo('akses_sap_sync');

        return $user;
    }

    protected function createValidatorUser(): User
    {
        $user = User::factory()->create(['project' => '000H']);
        $user->assignRole('admin');
        $user->givePermissionTo(['akses_sap_sync', 'validate_vj']);

        return $user;
    }

    protected function createJournal(array $overrides = []): VerificationJournal
    {
        $creator = User::factory()->create();

        return VerificationJournal::query()->create(array_merge([
            'nomor' => 'VJ'.uniqid(),
            'date' => now()->toDateString(),
            'project' => '000H',
            'amount' => 1000,
            'created_by' => $creator->id,
            'validation_status' => VerificationJournal::VALIDATION_PENDING,
            'sap_submission_attempts' => 0,
        ], $overrides));
    }

    public function test_pending_journal_cannot_be_submitted_to_sap(): void
    {
        $user = $this->createCashierUser();
        $journal = $this->createJournal();

        $this->actingAs($user)
            ->post(route('accounting.sap-sync.submit_to_sap'), [
                'verification_journal_id' => $journal->id,
            ])
            ->assertRedirect(route('accounting.sap-sync.show', $journal->id))
            ->assertSessionHas('error', 'This journal must be validated before it can be submitted to SAP B1.');
    }

    public function test_validator_can_validate_pending_journal(): void
    {
        $validator = $this->createValidatorUser();
        $journal = $this->createJournal();

        $this->actingAs($validator)
            ->post(route('accounting.sap-sync.validate'), [
                'verification_journal_id' => $journal->id,
            ])
            ->assertRedirect(route('accounting.sap-sync.show', $journal->id))
            ->assertSessionHas('success');

        $journal->refresh();
        $this->assertSame(VerificationJournal::VALIDATION_VALIDATED, $journal->validation_status);
        $this->assertSame($validator->id, $journal->validated_by);
        $this->assertNotNull($journal->validated_at);
    }

    public function test_validator_can_reject_pending_journal_with_reason(): void
    {
        $validator = $this->createValidatorUser();
        $journal = $this->createJournal(['type' => 'bank', 'status' => 'submitted']);

        $this->actingAs($validator)
            ->post(route('accounting.sap-sync.reject'), [
                'verification_journal_id' => $journal->id,
                'rejection_reason' => 'Incorrect account mapping',
            ])
            ->assertRedirect(route('accounting.sap-sync.show', $journal->id))
            ->assertSessionHas('success');

        $journal->refresh();
        $this->assertSame(VerificationJournal::VALIDATION_REJECTED, $journal->validation_status);
        $this->assertSame('Incorrect account mapping', $journal->rejection_reason);
        $this->assertSame('draft', $journal->status);
    }

    public function test_validated_journal_can_be_submitted_to_sap(): void
    {
        $user = $this->createCashierUser();
        $journal = $this->createJournal([
            'validation_status' => VerificationJournal::VALIDATION_VALIDATED,
            'validated_by' => $user->id,
            'validated_at' => now(),
        ]);

        $this->mock(SapJournalSubmissionService::class, function ($mock) {
            $mock->shouldReceive('submit')
                ->once()
                ->andReturn([
                    'success' => true,
                    'message' => 'Submitted successfully',
                    'sap_journal_no' => 'SAP-999',
                ]);
        });

        $this->actingAs($user)
            ->post(route('accounting.sap-sync.submit_to_sap'), [
                'verification_journal_id' => $journal->id,
            ])
            ->assertRedirect(route('accounting.sap-sync.show', $journal->id))
            ->assertSessionHas('success');
    }

    public function test_bulk_submit_skips_unvalidated_journals(): void
    {
        $user = $this->createCashierUser();
        $pending = $this->createJournal();
        $validated = $this->createJournal([
            'validation_status' => VerificationJournal::VALIDATION_VALIDATED,
            'validated_by' => $user->id,
            'validated_at' => now(),
        ]);

        $this->mock(SapJournalSubmissionService::class, function ($mock) {
            $mock->shouldReceive('submit')
                ->once()
                ->andReturn([
                    'success' => true,
                    'message' => 'Submitted successfully',
                    'sap_journal_no' => 'SAP-999',
                ]);
        });

        $this->actingAs($user)
            ->post(route('accounting.sap-sync.bulk_submit'), [
                'verification_journal_ids' => [$pending->id, $validated->id],
            ])
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_show_page_displays_validation_required_message_for_pending_journal(): void
    {
        $user = $this->createCashierUser();
        $journal = $this->createJournal();

        $this->actingAs($user)
            ->get(route('accounting.sap-sync.show', $journal->id))
            ->assertOk()
            ->assertSee('This journal must be validated before it can be submitted to SAP B1.', false)
            ->assertDontSee('Submit to SAP B1', false);
    }

    public function test_show_page_displays_submit_button_for_validated_journal(): void
    {
        $user = $this->createCashierUser();
        $journal = $this->createJournal([
            'validation_status' => VerificationJournal::VALIDATION_VALIDATED,
            'validated_by' => $user->id,
            'validated_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('accounting.sap-sync.show', $journal->id))
            ->assertOk()
            ->assertSee('Submit to SAP B1', false);
    }

    public function test_user_without_validate_permission_cannot_validate(): void
    {
        $user = $this->createCashierUser();
        $journal = $this->createJournal();

        $this->actingAs($user)
            ->post(route('accounting.sap-sync.validate'), [
                'verification_journal_id' => $journal->id,
            ])
            ->assertForbidden();
    }

    public function test_cashier_can_change_vj_detail_project(): void
    {
        $user = $this->createCashierUser();
        $journal = $this->createJournal([
            'project' => '000H',
            'created_by' => $user->id,
        ]);
        $detail = VerificationJournalDetail::query()->create([
            'verification_journal_id' => $journal->id,
            'realization_date' => now()->toDateString(),
            'account_code' => '61202001',
            'debit_credit' => 'debit',
            'description' => 'Test line',
            'project' => '000H',
            'cost_center' => '100',
            'amount' => 1000,
        ]);

        $this->actingAs($user)
            ->post(route('accounting.sap-sync.update_detail'), [
                'vj_detail_id' => $detail->id,
                'account_code' => '61202001',
                'project' => '022C',
                'cost_center' => '100',
                'description' => 'Test line',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSame('022C', $detail->fresh()->project);
    }

    public function test_site_cashier_cannot_change_vj_detail_project(): void
    {
        $user = User::factory()->create(['project' => '022C']);
        $user->assignRole('cashier_022');
        $user->givePermissionTo('akses_sap_sync');

        $journal = $this->createJournal([
            'project' => '022C',
            'created_by' => $user->id,
        ]);
        $detail = VerificationJournalDetail::query()->create([
            'verification_journal_id' => $journal->id,
            'realization_date' => now()->toDateString(),
            'account_code' => '61202001',
            'debit_credit' => 'debit',
            'description' => 'Test line',
            'project' => '022C',
            'cost_center' => '100',
            'amount' => 1000,
        ]);

        $this->actingAs($user)
            ->post(route('accounting.sap-sync.update_detail'), [
                'vj_detail_id' => $detail->id,
                'account_code' => '61202001',
                'project' => '000H',
                'cost_center' => '100',
                'description' => 'Test line',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSame('022C', $detail->fresh()->project);
    }

    public function test_cashier_can_see_sap_info_management_buttons(): void
    {
        $user = $this->createCashierUser();
        $journal = $this->createJournal([
            'validation_status' => VerificationJournal::VALIDATION_VALIDATED,
            'validated_by' => $user->id,
            'validated_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('accounting.sap-sync.show', $journal->id))
            ->assertOk()
            ->assertSee('data-target="#update-sap"', false)
            ->assertSee('cancel-sap-info-form', false);
    }

    public function test_cashier_cannot_manage_sap_info_while_pending(): void
    {
        $user = $this->createCashierUser();
        $journal = $this->createJournal([
            'validation_status' => VerificationJournal::VALIDATION_PENDING,
        ]);

        $this->actingAs($user)
            ->get(route('accounting.sap-sync.show', $journal->id))
            ->assertOk()
            ->assertSee('cancel-sap-info-form', false)
            ->assertSee('disabled', false)
            ->assertDontSee('id="update-sap"', false);

        $this->actingAs($user)
            ->post(route('accounting.sap-sync.update_sap_info'), [
                'verification_journal_id' => $journal->id,
                'sap_posting_date' => now()->toDateString(),
                'sap_journal_no' => 'SAP-123',
            ])
            ->assertForbidden();

        $this->actingAs($user)
            ->post(route('accounting.sap-sync.cancel_sap_info'), [
                'verification_journal_id' => $journal->id,
            ])
            ->assertForbidden();

        $journal->refresh();
        $this->assertNull($journal->sap_journal_no);
    }

    public function test_update_sap_info_requires_validated_status(): void
    {
        $user = $this->createCashierUser();
        $journal = $this->createJournal([
            'validation_status' => VerificationJournal::VALIDATION_PENDING,
        ]);

        $this->actingAs($user)
            ->post(route('accounting.sap-sync.update_sap_info'), [
                'verification_journal_id' => $journal->id,
                'sap_posting_date' => now()->toDateString(),
                'sap_journal_no' => 'SAP-BYPASS',
            ])
            ->assertForbidden();

        $this->assertNull($journal->fresh()->sap_journal_no);
    }

    public function test_site_cashier_cannot_see_sap_info_management_buttons(): void
    {
        $user = User::factory()->create(['project' => '022C']);
        $user->assignRole('cashier_022');
        $user->givePermissionTo('akses_sap_sync');

        $journal = $this->createJournal([
            'project' => '022C',
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('accounting.sap-sync.show', $journal->id))
            ->assertOk()
            ->assertDontSee('data-target="#update-sap"', false)
            ->assertDontSee('cancel-sap-info-form', false);
    }

    public function test_site_cashier_cannot_update_or_cancel_sap_info(): void
    {
        $user = User::factory()->create(['project' => '022C']);
        $user->assignRole('cashier_022');
        $user->givePermissionTo('akses_sap_sync');

        $journal = $this->createJournal([
            'project' => '022C',
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->post(route('accounting.sap-sync.update_sap_info'), [
                'verification_journal_id' => $journal->id,
                'sap_posting_date' => now()->toDateString(),
                'sap_journal_no' => 'SAP-123',
            ])
            ->assertForbidden();

        $this->actingAs($user)
            ->post(route('accounting.sap-sync.cancel_sap_info'), [
                'verification_journal_id' => $journal->id,
            ])
            ->assertForbidden();
    }

    public function test_creator_can_edit_rejected_journal_details(): void
    {
        $creator = User::factory()->create(['project' => '022C']);
        $creator->givePermissionTo('akses_sap_sync');

        $journal = $this->createJournal([
            'project' => '022C',
            'created_by' => $creator->id,
            'validation_status' => VerificationJournal::VALIDATION_REJECTED,
            'rejection_reason' => 'Fix account code',
        ]);

        $this->actingAs($creator)
            ->get(route('accounting.sap-sync.show', $journal->id))
            ->assertOk()
            ->assertSee('Edit Details', false)
            ->assertSee(route('accounting.sap-sync.edit_vjdetail_display', ['vj_id' => $journal->id]), false);

        $this->actingAs($creator)
            ->get(route('accounting.sap-sync.edit_vjdetail_display', ['vj_id' => $journal->id]))
            ->assertOk();
    }

    public function test_validator_cannot_edit_rejected_journal_they_do_not_own(): void
    {
        $creator = User::factory()->create(['project' => '022C']);
        $validator = $this->createValidatorUser();

        $journal = $this->createJournal([
            'project' => '022C',
            'created_by' => $creator->id,
            'validation_status' => VerificationJournal::VALIDATION_REJECTED,
            'rejection_reason' => 'Fix account code',
        ]);

        $this->actingAs($validator)
            ->get(route('accounting.sap-sync.show', $journal->id))
            ->assertOk()
            ->assertDontSee('Edit Details', false);

        $this->actingAs($validator)
            ->get(route('accounting.sap-sync.edit_vjdetail_display', ['vj_id' => $journal->id]))
            ->assertForbidden();
    }

    public function test_user_with_edit_verification_project_can_edit_rejected_journal(): void
    {
        $creator = User::factory()->create(['project' => '022C']);
        $editor = User::factory()->create(['project' => '022C']);
        $editor->givePermissionTo(['akses_sap_sync', 'edit_verification_project']);

        $journal = $this->createJournal([
            'project' => '022C',
            'created_by' => $creator->id,
            'validation_status' => VerificationJournal::VALIDATION_REJECTED,
            'rejection_reason' => 'Fix account code',
        ]);

        $this->actingAs($editor)
            ->get(route('accounting.sap-sync.edit_vjdetail_display', ['vj_id' => $journal->id]))
            ->assertOk();
    }

    public function test_sap_info_buttons_are_disabled_when_journal_is_rejected(): void
    {
        $user = $this->createCashierUser();
        $journal = $this->createJournal([
            'validation_status' => VerificationJournal::VALIDATION_REJECTED,
            'rejection_reason' => 'Needs correction',
        ]);

        $response = $this->actingAs($user)
            ->get(route('accounting.sap-sync.show', $journal->id));

        $response->assertOk()
            ->assertSee('cancel-sap-info-form', false)
            ->assertSee('disabled', false)
            ->assertDontSee('id="update-sap"', false);

        $this->actingAs($user)
            ->post(route('accounting.sap-sync.update_sap_info'), [
                'verification_journal_id' => $journal->id,
                'sap_posting_date' => now()->toDateString(),
                'sap_journal_no' => 'SAP-123',
            ])
            ->assertForbidden();
    }
}
