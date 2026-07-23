<?php

namespace Tests\Feature;

use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\SapSubmissionLog;
use App\Models\User;
use App\Services\SapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class JournalEntrySapSubmissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'create_manual_journal_entry'], ['guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'cancel_sap_journal'], ['guard_name' => 'web']);

        foreach (['admin', 'cashier'] as $roleName) {
            Role::query()->firstOrCreate(['name' => $roleName], ['guard_name' => 'web']);
        }
    }

    protected function authorizedUser(bool $withCancel = true): User
    {
        $user = User::factory()->create();
        $user->assignRole('cashier');
        $user->givePermissionTo('create_manual_journal_entry');

        if ($withCancel) {
            $user->givePermissionTo('cancel_sap_journal');
        }

        return $user;
    }

    protected function createDraftJournal(User $user): JournalEntry
    {
        $entry = JournalEntry::factory()->create(['created_by' => $user->id]);

        JournalEntryLine::factory()->create([
            'journal_entry_id' => $entry->id,
            'line_no' => 1,
            'account_code' => '11001',
            'debit_credit' => 'debit',
            'amount' => 1000,
        ]);

        JournalEntryLine::factory()->create([
            'journal_entry_id' => $entry->id,
            'line_no' => 2,
            'account_code' => '21001',
            'debit_credit' => 'credit',
            'amount' => 1000,
        ]);

        return $entry;
    }

    public function test_submit_to_sap_success(): void
    {
        $user = $this->authorizedUser();
        $entry = $this->createDraftJournal($user);

        $this->mock(SapService::class, function ($mock) {
            $mock->shouldReceive('createJournalEntry')
                ->once()
                ->andReturn([
                    'success' => true,
                    'journal_number' => 'SAP-JE-100',
                    'data' => [
                        'DocEntry' => 9001,
                        'JdtNum' => 9001,
                        'Number' => 'SAP-JE-100',
                    ],
                ]);
        });

        $this->actingAs($user)
            ->post(route('accounting.journal-entries.submit_to_sap', $entry->id))
            ->assertRedirect(route('accounting.journal-entries.show', $entry->id))
            ->assertSessionHas('success');

        $entry->refresh();
        $this->assertEquals('success', $entry->sap_submission_status);
        $this->assertEquals('SAP-JE-100', $entry->sap_journal_no);
        $this->assertEquals('9001', $entry->sap_je_jdt_num);

        $this->assertDatabaseHas('sap_submission_logs', [
            'journal_entry_id' => $entry->id,
            'document_type' => 'manual_journal_entry',
            'status' => 'success',
            'action' => 'submission',
        ]);
    }

    public function test_submit_to_sap_failure_records_log(): void
    {
        $user = $this->authorizedUser();
        $entry = $this->createDraftJournal($user);

        $this->mock(SapService::class, function ($mock) {
            $mock->shouldReceive('createJournalEntry')
                ->once()
                ->andThrow(new \Exception('SAP connection failed'));
        });

        $this->actingAs($user)
            ->post(route('accounting.journal-entries.submit_to_sap', $entry->id))
            ->assertRedirect(route('accounting.journal-entries.show', $entry->id))
            ->assertSessionHas('error');

        $entry->refresh();
        $this->assertEquals('failed', $entry->sap_submission_status);
        $this->assertStringContainsString('SAP connection failed', $entry->sap_submission_error);

        $this->assertTrue(
            SapSubmissionLog::where('journal_entry_id', $entry->id)->where('status', 'failed')->exists()
        );
    }

    public function test_reverse_requires_cancel_permission(): void
    {
        $user = $this->authorizedUser(false);
        $entry = JournalEntry::factory()->posted()->create([
            'created_by' => $user->id,
            'sap_je_jdt_num' => '5001',
        ]);

        $this->from(route('accounting.journal-entries.show', $entry->id))
            ->actingAs($user)
            ->post(route('accounting.journal-entries.reverse_to_sap', $entry->id), [
                'reason' => 'Wrong amount',
            ])
            ->assertRedirect(route('accounting.journal-entries.show', $entry->id))
            ->assertSessionHas('alert_type', 'error');
    }

    public function test_reverse_to_sap_success(): void
    {
        $user = $this->authorizedUser();
        $entry = JournalEntry::factory()->posted()->create([
            'created_by' => $user->id,
            'sap_je_jdt_num' => '5001',
            'sap_submitted_by' => $user->id,
        ]);

        $this->mock(SapService::class, function ($mock) {
            $mock->shouldReceive('cancelJournalEntry')
                ->once()
                ->with('5001')
                ->andReturn([
                    'success' => true,
                    'reversal_journal_no' => 'SAP-REV-200',
                ]);
        });

        $this->actingAs($user)
            ->post(route('accounting.journal-entries.reverse_to_sap', $entry->id), [
                'reason' => 'Posted in error',
            ])
            ->assertRedirect(route('accounting.journal-entries.show', $entry->id))
            ->assertSessionHas('success');

        $entry->refresh();
        $this->assertNotNull($entry->sap_reversed_at);
        $this->assertEquals('SAP-REV-200', $entry->sap_reversal_journal_no);

        $this->assertDatabaseHas('sap_submission_logs', [
            'journal_entry_id' => $entry->id,
            'action' => 'reversal',
            'status' => 'success',
        ]);
    }
}
