<?php

namespace Tests\Feature;

use App\Models\Realization;
use App\Models\User;
use App\Models\VerificationJournal;
use App\Models\VerificationJournalDetail;
use App\Services\SapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SapSyncReversalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'akses_sap_sync'], ['guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'cancel_sap_journal'], ['guard_name' => 'web']);

        foreach (['superadmin', 'admin', 'cashier', 'approver', 'approver_bo', 'cashier_bo'] as $roleName) {
            Role::query()->firstOrCreate(['name' => $roleName], ['guard_name' => 'web']);
        }
    }

    protected function createUserWithPermission(string $role, bool $withCancelPermission = true): User
    {
        $user = User::factory()->create(['project' => '000H']);
        $user->assignRole($role);
        $user->givePermissionTo('akses_sap_sync');

        if ($withCancelPermission) {
            $user->givePermissionTo('cancel_sap_journal');
        }

        return $user;
    }

    protected function createPostedJournal(array $overrides = []): VerificationJournal
    {
        $creator = User::factory()->create();

        return VerificationJournal::query()->create(array_merge([
            'nomor' => 'VJ'.uniqid(),
            'date' => now()->toDateString(),
            'project' => '000H',
            'amount' => 1000,
            'created_by' => $creator->id,
            'sap_journal_no' => 'SAP-1001',
            'sap_je_jdt_num' => '5001',
            'sap_posting_date' => now()->toDateString(),
            'posted_by' => $creator->id,
            'sap_submission_status' => 'success',
            'sap_submission_attempts' => 1,
        ], $overrides));
    }

    protected function createJournalDetail(VerificationJournal $vj, string $realizationNo = 'RLZ-001'): VerificationJournalDetail
    {
        return VerificationJournalDetail::query()->create([
            'verification_journal_id' => $vj->id,
            'realization_date' => now()->toDateString(),
            'account_code' => '1100',
            'debit_credit' => 'debit',
            'description' => 'Test line',
            'sap_journal_no' => $vj->sap_journal_no,
            'realization_no' => $realizationNo,
            'project' => $vj->project,
            'cost_center' => 'FIN',
            'amount' => 1000,
        ]);
    }

    protected function createRealization(string $nomor, string $status = 'close'): Realization
    {
        $user = User::factory()->create();

        return Realization::query()->create([
            'nomor' => $nomor,
            'payreq_id' => 1,
            'user_id' => $user->id,
            'project' => '000H',
            'status' => $status,
        ]);
    }

    public function test_user_without_cancel_permission_cannot_reverse(): void
    {
        $user = $this->createUserWithPermission('admin', false);
        $journal = $this->createPostedJournal();

        $this->from(route('accounting.sap-sync.show', $journal->id))
            ->actingAs($user)
            ->post(route('accounting.sap-sync.reverse_to_sap'), [
                'verification_journal_id' => $journal->id,
                'reason' => 'Wrong amount',
            ])
            ->assertRedirect(route('accounting.sap-sync.show', $journal->id))
            ->assertSessionHas('alert_type', 'error');

        $journal->refresh();
        $this->assertEquals('SAP-1001', $journal->sap_journal_no);
        $this->assertNull($journal->sap_reversed_at);
    }

    public function test_user_with_cancel_permission_can_access_automated_reversal(): void
    {
        $user = $this->createUserWithPermission('cashier');
        $journal = $this->createPostedJournal();
        $this->createJournalDetail($journal, 'RLZ-AUTO-1');
        $realization = $this->createRealization('RLZ-AUTO-1', 'close');

        $this->mock(SapService::class, function ($mock) {
            $mock->shouldReceive('cancelJournalEntry')
                ->once()
                ->with('5001')
                ->andReturn([
                    'success' => true,
                    'jdt_num' => '5001',
                    'reversal_journal_no' => 'SAP-REV-99',
                ]);
        });

        $this->actingAs($user)
            ->post(route('accounting.sap-sync.reverse_to_sap'), [
                'verification_journal_id' => $journal->id,
                'reason' => 'Wrong cost center',
            ])
            ->assertRedirect(route('accounting.sap-sync.show', $journal->id))
            ->assertSessionHas('success');

        $journal->refresh();
        $realization->refresh();

        $this->assertNull($journal->sap_journal_no);
        $this->assertNull($journal->sap_je_jdt_num);
        $this->assertNull($journal->posted_by);
        $this->assertNotNull($journal->sap_reversed_at);
        $this->assertEquals($user->id, $journal->sap_reversed_by);
        $this->assertEquals('Wrong cost center', $journal->sap_reversal_reason);
        $this->assertEquals('SAP-REV-99', $journal->sap_reversal_journal_no);
        $this->assertEquals('verification-complete', $realization->status);

        $this->assertDatabaseHas('verification_journal_details', [
            'verification_journal_id' => $journal->id,
            'sap_journal_no' => null,
        ]);

        $this->assertDatabaseHas('sap_submission_logs', [
            'verification_journal_id' => $journal->id,
            'action' => 'reversal',
            'status' => 'success',
            'sap_journal_number' => 'SAP-1001',
        ]);
    }

    public function test_automated_reversal_failure_keeps_journal_posted(): void
    {
        $user = $this->createUserWithPermission('approver');
        $journal = $this->createPostedJournal();
        $this->createJournalDetail($journal);

        $this->mock(SapService::class, function ($mock) {
            $mock->shouldReceive('cancelJournalEntry')
                ->once()
                ->andThrow(new \Exception('Period already closed'));
        });

        $this->actingAs($user)
            ->post(route('accounting.sap-sync.reverse_to_sap'), [
                'verification_journal_id' => $journal->id,
                'reason' => 'Need to reverse',
            ])
            ->assertRedirect(route('accounting.sap-sync.show', $journal->id))
            ->assertSessionHas('error');

        $journal->refresh();

        $this->assertEquals('SAP-1001', $journal->sap_journal_no);
        $this->assertEquals('5001', $journal->sap_je_jdt_num);
        $this->assertNull($journal->sap_reversed_at);

        $this->assertDatabaseHas('sap_submission_logs', [
            'verification_journal_id' => $journal->id,
            'action' => 'reversal',
            'status' => 'failed',
            'error_message' => 'Period already closed',
        ]);
    }

    public function test_manual_reversal_succeeds_without_calling_sap(): void
    {
        $user = $this->createUserWithPermission('superadmin');
        $journal = $this->createPostedJournal([
            'sap_je_jdt_num' => null,
        ]);
        $this->createJournalDetail($journal, 'RLZ-MANUAL-1');
        $realization = $this->createRealization('RLZ-MANUAL-1', 'close');

        $sapMock = $this->mock(SapService::class);
        $sapMock->shouldNotReceive('cancelJournalEntry');

        $this->actingAs($user)
            ->post(route('accounting.sap-sync.record_manual_reversal'), [
                'verification_journal_id' => $journal->id,
                'reason' => 'Reversed in SAP client',
                'sap_reversal_journal_no' => 'SAP-REV-11',
            ])
            ->assertRedirect(route('accounting.sap-sync.show', $journal->id))
            ->assertSessionHas('success');

        $journal->refresh();
        $realization->refresh();

        $this->assertNull($journal->sap_journal_no);
        $this->assertEquals('Reversed in SAP client', $journal->sap_reversal_reason);
        $this->assertEquals('SAP-REV-11', $journal->sap_reversal_journal_no);
        $this->assertEquals('verification-complete', $realization->status);

        $this->assertDatabaseHas('sap_submission_logs', [
            'verification_journal_id' => $journal->id,
            'action' => 'reversal',
            'status' => 'success',
        ]);
    }

    public function test_reversal_blocked_when_delivery_attached(): void
    {
        $user = $this->createUserWithPermission('cashier');
        $journal = $this->createPostedJournal([
            'delivery_id' => 99,
        ]);

        $this->mock(SapService::class, function ($mock) {
            $mock->shouldNotReceive('cancelJournalEntry');
        });

        $this->actingAs($user)
            ->post(route('accounting.sap-sync.reverse_to_sap'), [
                'verification_journal_id' => $journal->id,
                'reason' => 'Should be blocked',
            ])
            ->assertRedirect(route('accounting.sap-sync.show', $journal->id))
            ->assertSessionHas('error');

        $journal->refresh();
        $this->assertEquals('SAP-1001', $journal->sap_journal_no);
        $this->assertNull($journal->sap_reversed_at);
    }

    public function test_show_page_displays_reverse_button_for_permitted_user(): void
    {
        $user = $this->createUserWithPermission('approver');
        $journal = $this->createPostedJournal();

        $this->actingAs($user)
            ->get(route('accounting.sap-sync.show', $journal->id))
            ->assertOk()
            ->assertSee('Reverse in SAP B1', false);
    }

    public function test_show_page_hides_reverse_button_without_permission(): void
    {
        $user = $this->createUserWithPermission('admin', false);
        $journal = $this->createPostedJournal();

        $this->actingAs($user)
            ->get(route('accounting.sap-sync.show', $journal->id))
            ->assertOk()
            ->assertDontSee('Reverse in SAP B1', false)
            ->assertDontSee('Record Manual Reversal', false);
    }

    public function test_reversal_log_page_loads(): void
    {
        $user = $this->createUserWithPermission('superadmin');

        $this->actingAs($user)
            ->get(route('accounting.sap-sync.index', ['page' => 'reversal-log']))
            ->assertOk()
            ->assertSee('Reversal Log', false);
    }

    public function test_reversal_log_data_lists_manual_reversal(): void
    {
        $user = $this->createUserWithPermission('superadmin');
        $journal = $this->createPostedJournal([
            'sap_je_jdt_num' => null,
        ]);
        $this->createJournalDetail($journal, 'RLZ-LOG-1');
        $this->createRealization('RLZ-LOG-1', 'close');

        $this->actingAs($user)
            ->post(route('accounting.sap-sync.record_manual_reversal'), [
                'verification_journal_id' => $journal->id,
                'reason' => 'Wrong project code',
                'sap_reversal_journal_no' => 'SAP-REV-77',
            ])
            ->assertSessionHas('success');

        $response = $this->actingAs($user)
            ->getJson(route('accounting.sap-sync.reversal_log_data'));

        $response->assertOk();
        $data = $response->json('data');

        $this->assertCount(1, $data);
        $this->assertEquals($journal->nomor, $data[0]['journal_no']);
        $this->assertEquals('Manual', $data[0]['type']);
        $this->assertEquals('SAP-1001', $data[0]['sap_journal_number']);
        $this->assertEquals('SAP-REV-77', $data[0]['sap_doc_num']);
        $this->assertEquals('Wrong project code', $data[0]['error_message']);
    }

    public function test_bo_user_only_sees_001h_reversals_in_log(): void
    {
        $boUser = $this->createUserWithPermission('cashier_bo');

        $otherProjectJournal = $this->createPostedJournal([
            'project' => '017C',
            'sap_je_jdt_num' => null,
        ]);
        $this->createJournalDetail($otherProjectJournal, 'RLZ-LOG-2');

        $superadmin = $this->createUserWithPermission('superadmin');
        $this->actingAs($superadmin)
            ->post(route('accounting.sap-sync.record_manual_reversal'), [
                'verification_journal_id' => $otherProjectJournal->id,
                'reason' => 'Not visible to BO',
            ])
            ->assertSessionHas('success');

        $response = $this->actingAs($boUser)
            ->getJson(route('accounting.sap-sync.reversal_log_data'));

        $response->assertOk();
        $this->assertCount(0, $response->json('data'));
    }
}
