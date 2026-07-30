<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VerificationJournal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardVjPendingValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'validate_vj'], ['guard_name' => 'web']);

        foreach (['admin', 'cashier', 'cashier_bo'] as $roleName) {
            Role::query()->firstOrCreate(['name' => $roleName], ['guard_name' => 'web']);
        }
    }

    protected function createValidator(): User
    {
        $user = User::factory()->create(['project' => '000H']);
        $user->assignRole('admin');
        $user->givePermissionTo('validate_vj');

        return $user;
    }

    protected function createBoValidator(): User
    {
        $user = User::factory()->create(['project' => '001H']);
        $user->assignRole('cashier_bo');
        $user->givePermissionTo('validate_vj');

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

    public function test_dashboard_shows_pending_vj_count_for_validators(): void
    {
        $validator = $this->createValidator();

        $this->createJournal(['validation_status' => VerificationJournal::VALIDATION_PENDING]);
        $this->createJournal(['validation_status' => VerificationJournal::VALIDATION_PENDING]);
        $this->createJournal(['validation_status' => VerificationJournal::VALIDATION_REJECTED]);
        $this->createJournal(['validation_status' => VerificationJournal::VALIDATION_VALIDATED]);

        $this->actingAs($validator)
            ->get(route('dashboard.index'))
            ->assertOk()
            ->assertSee('VJ pending validation', false)
            ->assertSee('data-dashboard-pending-vj-validation="2"', false);
    }

    public function test_dashboard_hides_vj_card_without_permission(): void
    {
        $user = User::factory()->create(['project' => '000H']);
        $user->assignRole('cashier');

        $this->createJournal(['validation_status' => VerificationJournal::VALIDATION_PENDING]);

        $this->actingAs($user)
            ->get(route('dashboard.index'))
            ->assertOk()
            ->assertDontSee('VJ pending validation', false)
            ->assertDontSee('data-dashboard-pending-vj-validation=', false);
    }

    public function test_bo_restricted_validator_only_counts_001h_project_vjs(): void
    {
        $validator = $this->createBoValidator();

        $this->createJournal([
            'project' => '001H',
            'validation_status' => VerificationJournal::VALIDATION_PENDING,
        ]);
        $this->createJournal([
            'project' => '022C',
            'validation_status' => VerificationJournal::VALIDATION_PENDING,
        ]);

        $this->actingAs($validator)
            ->get(route('dashboard.index'))
            ->assertOk()
            ->assertSee('VJ pending validation', false)
            ->assertSee('data-dashboard-pending-vj-validation="1"', false);
    }

    public function test_posted_pending_journal_is_excluded_from_dashboard_count(): void
    {
        $validator = $this->createValidator();

        $this->createJournal([
            'validation_status' => VerificationJournal::VALIDATION_PENDING,
            'sap_journal_no' => '267673958',
        ]);
        $this->createJournal([
            'validation_status' => VerificationJournal::VALIDATION_PENDING,
        ]);

        $this->actingAs($validator)
            ->get(route('dashboard.index'))
            ->assertOk()
            ->assertSee('data-dashboard-pending-vj-validation="1"', false);
    }
}
