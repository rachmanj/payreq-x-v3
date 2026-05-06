<?php

namespace Tests\Feature;

use App\Models\OverdueExtension;
use App\Models\Payreq;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OverdueExtensionTest extends TestCase
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

    public function test_guest_is_redirected_from_extensions_index(): void
    {
        $this->get(route('document-overdue.extensions.index'))
            ->assertRedirect(route('login'));
    }

    public function test_user_without_approve_permission_cannot_view_extensions_index(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('document-overdue.extensions.index'))
            ->assertForbidden();
    }

    public function test_user_with_approve_permission_can_view_extensions_index(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('approve_overdue_extension');

        $this->actingAs($user)
            ->get(route('document-overdue.extensions.index'))
            ->assertOk();
    }

    public function test_admin_role_can_view_extensions_index_without_explicit_permission(): void
    {
        Role::query()->firstOrCreate(
            ['name' => 'admin'],
            ['guard_name' => 'web']
        );

        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->actingAs($user)
            ->get(route('document-overdue.extensions.index'))
            ->assertOk();
    }

    public function test_user_can_submit_extension_for_eligible_overdue_payreq(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-10 12:00:00'));

        $user = User::factory()->create();
        $payreq = Payreq::query()->create([
            'user_id' => $user->id,
            'nomor' => 'EXT-TEST-1',
            'type' => 'advance',
            'status' => 'paid',
            'amount' => 1000,
            'due_date' => '2026-05-01',
            'project' => '000H',
            'remarks' => 'Travel advance',
        ]);

        $payload = [
            'document_type' => 'payreq',
            'document_id' => $payreq->id,
            'requested_due_date' => '2026-05-15',
            'reason' => 'Need more time to complete realization paperwork.',
        ];

        $this->actingAs($user)
            ->from(route('user-payreqs.index'))
            ->post(route('document-overdue.extensions.store'), $payload)
            ->assertRedirect(route('user-payreqs.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('overdue_extensions', [
            'document_type' => OverdueExtension::DOCUMENT_PAYREQ,
            'document_id' => $payreq->id,
            'user_id' => $user->id,
            'status' => OverdueExtension::STATUS_PENDING,
        ]);

        Carbon::setTestNow();
    }

    public function test_user_cannot_submit_extension_for_ineligible_project(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-10 12:00:00'));

        $user = User::factory()->create();
        $payreq = Payreq::query()->create([
            'user_id' => $user->id,
            'nomor' => 'EXT-TEST-2',
            'type' => 'advance',
            'status' => 'paid',
            'amount' => 1000,
            'due_date' => '2026-05-01',
            'project' => '022C',
            'remarks' => 'Other project',
        ]);

        $payload = [
            'document_type' => 'payreq',
            'document_id' => $payreq->id,
            'requested_due_date' => '2026-05-15',
            'reason' => 'Request',
        ];

        $this->actingAs($user)
            ->post(route('document-overdue.extensions.store'), $payload)
            ->assertForbidden();

        Carbon::setTestNow();
    }

    public function test_user_cannot_submit_second_pending_extension_for_same_document(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-10 12:00:00'));

        $user = User::factory()->create();
        $payreq = Payreq::query()->create([
            'user_id' => $user->id,
            'nomor' => 'EXT-TEST-3',
            'type' => 'advance',
            'status' => 'paid',
            'amount' => 1000,
            'due_date' => '2026-05-01',
            'project' => 'APS',
            'remarks' => 'APS advance',
        ]);

        OverdueExtension::query()->create([
            'document_type' => OverdueExtension::DOCUMENT_PAYREQ,
            'document_id' => $payreq->id,
            'user_id' => $user->id,
            'current_due_date' => '2026-05-01',
            'requested_due_date' => '2026-05-20',
            'reason' => 'First',
            'status' => OverdueExtension::STATUS_PENDING,
        ]);

        $payload = [
            'document_type' => 'payreq',
            'document_id' => $payreq->id,
            'requested_due_date' => '2026-05-25',
            'reason' => 'Second pending',
        ];

        $this->actingAs($user)
            ->from(route('user-payreqs.index'))
            ->post(route('document-overdue.extensions.store'), $payload)
            ->assertSessionHasErrors('document_id');

        Carbon::setTestNow();
    }

    public function test_user_without_approve_permission_cannot_post_direct_payreq_extend(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('document-overdue.payreq.extend'), [
                'payreq_id' => 1,
                'new_due_date' => '2026-06-01',
            ])
            ->assertForbidden();
    }

    public function test_approver_can_approve_pending_extension_and_updates_due_date(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-10 12:00:00'));

        $owner = User::factory()->create();
        $approver = User::factory()->create();
        $approver->givePermissionTo('approve_overdue_extension');

        $payreq = Payreq::query()->create([
            'user_id' => $owner->id,
            'nomor' => 'EXT-TEST-4',
            'type' => 'advance',
            'status' => 'paid',
            'amount' => 5000,
            'due_date' => '2026-05-01',
            'project' => '000H',
            'remarks' => 'Field work',
        ]);

        $extension = OverdueExtension::query()->create([
            'document_type' => OverdueExtension::DOCUMENT_PAYREQ,
            'document_id' => $payreq->id,
            'user_id' => $owner->id,
            'current_due_date' => '2026-05-01',
            'requested_due_date' => '2026-05-30',
            'reason' => 'Delayed reporting',
            'status' => OverdueExtension::STATUS_PENDING,
        ]);

        $this->actingAs($approver)
            ->put(route('document-overdue.extensions.approve', $extension))
            ->assertRedirect(route('document-overdue.extensions.index'))
            ->assertSessionHas('success');

        $payreq->refresh();
        $this->assertSame('2026-05-30', Carbon::parse($payreq->due_date)->toDateString());

        $extension->refresh();
        $this->assertSame(OverdueExtension::STATUS_APPROVED, $extension->status);
        $this->assertSame($approver->id, $extension->reviewed_by);

        Carbon::setTestNow();
    }

    public function test_guest_is_redirected_from_user_overdue_documents_page(): void
    {
        $this->get(route('user-payreqs.overdue-documents.index'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_user_overdue_documents_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('user-payreqs.overdue-documents.index'))
            ->assertOk();
    }

    public function test_dashboard_shows_pending_overdue_extension_count_for_approvers(): void
    {
        $owner = User::factory()->create();
        $approver = User::factory()->create();
        $approver->givePermissionTo('approve_overdue_extension');

        $payreqOne = Payreq::query()->create([
            'user_id' => $owner->id,
            'nomor' => 'EXT-DASH-1',
            'type' => 'advance',
            'status' => 'paid',
            'amount' => 1000,
            'due_date' => '2026-05-01',
            'project' => '000H',
            'remarks' => 'Dashboard card test',
        ]);

        $payreqTwo = Payreq::query()->create([
            'user_id' => $owner->id,
            'nomor' => 'EXT-DASH-2',
            'type' => 'advance',
            'status' => 'paid',
            'amount' => 2000,
            'due_date' => '2026-05-02',
            'project' => 'APS',
            'remarks' => 'Dashboard card test',
        ]);

        foreach ([$payreqOne, $payreqTwo] as $payreq) {
            OverdueExtension::query()->create([
                'document_type' => OverdueExtension::DOCUMENT_PAYREQ,
                'document_id' => $payreq->id,
                'user_id' => $owner->id,
                'current_due_date' => $payreq->due_date,
                'requested_due_date' => '2026-05-30',
                'reason' => 'Pending review',
                'status' => OverdueExtension::STATUS_PENDING,
            ]);
        }

        $response = $this->actingAs($approver)
            ->get(route('dashboard.index'));

        $response->assertOk()
            ->assertSee(route('document-overdue.extensions.index'))
            ->assertSee('data-dashboard-pending-extension-requests="2"', false);
    }

    public function test_dashboard_hides_pending_overdue_extension_card_without_permission(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard.index'))
            ->assertOk()
            ->assertDontSee('Pending overdue extension requests', false);
    }
}
