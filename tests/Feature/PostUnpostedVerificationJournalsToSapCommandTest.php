<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VerificationJournal;
use App\Models\VerificationJournalDetail;
use App\Services\SapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostUnpostedVerificationJournalsToSapCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.sap.auto_submit_user_id' => null]);
    }

    protected function createAutoSubmitUser(): User
    {
        $user = User::factory()->create(['project' => '000H']);
        config(['services.sap.auto_submit_user_id' => $user->id]);

        return $user;
    }

    protected function createUnpostedJournal(array $overrides = []): VerificationJournal
    {
        $creator = User::factory()->create();

        return VerificationJournal::query()->create(array_merge([
            'nomor' => 'VJ'.uniqid(),
            'date' => now()->toDateString(),
            'project' => '000H',
            'amount' => 1000,
            'created_by' => $creator->id,
            'sap_journal_no' => null,
        ], $overrides));
    }

    protected function createBalancedJournalDetails(VerificationJournal $vj): void
    {
        VerificationJournalDetail::query()->create([
            'verification_journal_id' => $vj->id,
            'realization_date' => now()->toDateString(),
            'account_code' => '1100',
            'debit_credit' => 'debit',
            'description' => 'Debit line',
            'realization_no' => 'RLZ-'.uniqid(),
            'project' => $vj->project,
            'cost_center' => 'FIN',
            'amount' => 1000,
        ]);

        VerificationJournalDetail::query()->create([
            'verification_journal_id' => $vj->id,
            'realization_date' => now()->toDateString(),
            'account_code' => '2100',
            'debit_credit' => 'credit',
            'description' => 'Credit line',
            'realization_no' => 'RLZ-'.uniqid(),
            'project' => $vj->project,
            'cost_center' => 'FIN',
            'amount' => 1000,
        ]);
    }

    public function test_command_submits_recent_unposted_journal_successfully(): void
    {
        $user = $this->createAutoSubmitUser();
        $journal = $this->createUnpostedJournal();
        $this->createBalancedJournalDetails($journal);

        $this->mock(SapService::class, function ($mock) {
            $mock->shouldReceive('createJournalEntry')
                ->once()
                ->andReturn([
                    'success' => true,
                    'journal_number' => 'SAP-9001',
                    'doc_entry' => '9001',
                    'data' => [
                        'DocEntry' => 9001,
                        'Number' => 'SAP-9001',
                        'JdtNum' => 5001,
                    ],
                ]);
        });

        $this->artisan('sap:post-unposted-vj')
            ->assertSuccessful();

        $journal->refresh();

        $this->assertEquals('SAP-9001', $journal->sap_journal_no);
        $this->assertEquals('5001', $journal->sap_je_jdt_num);
        $this->assertEquals($user->id, $journal->posted_by);
        $this->assertEquals('success', $journal->sap_submission_status);

        $this->assertDatabaseHas('sap_submission_logs', [
            'verification_journal_id' => $journal->id,
            'user_id' => $user->id,
            'action' => 'submission',
            'status' => 'success',
            'sap_journal_number' => 'SAP-9001',
        ]);
    }

    public function test_command_skips_journals_older_than_thirty_days(): void
    {
        $this->createAutoSubmitUser();
        $journal = $this->createUnpostedJournal([
            'date' => now()->subDays(31)->toDateString(),
        ]);
        $this->createBalancedJournalDetails($journal);

        $sapMock = $this->mock(SapService::class);
        $sapMock->shouldNotReceive('createJournalEntry');

        $this->artisan('sap:post-unposted-vj')
            ->assertSuccessful()
            ->expectsOutput('No unposted verification journals found for automated SAP submission.');

        $journal->refresh();
        $this->assertNull($journal->sap_journal_no);
    }

    public function test_command_skips_journals_that_failed_twice(): void
    {
        $this->createAutoSubmitUser();
        $journal = $this->createUnpostedJournal([
            'sap_submission_status' => 'failed',
            'sap_submission_attempts' => 2,
            'sap_submission_error' => 'Previous SAP error',
        ]);
        $this->createBalancedJournalDetails($journal);

        $sapMock = $this->mock(SapService::class);
        $sapMock->shouldNotReceive('createJournalEntry');

        $this->artisan('sap:post-unposted-vj')
            ->assertSuccessful()
            ->expectsOutput('No unposted verification journals found for automated SAP submission.');

        $journal->refresh();
        $this->assertNull($journal->sap_journal_no);
        $this->assertEquals(2, $journal->sap_submission_attempts);
    }

    public function test_command_retries_journal_that_failed_once(): void
    {
        $user = $this->createAutoSubmitUser();
        $journal = $this->createUnpostedJournal([
            'sap_submission_status' => 'failed',
            'sap_submission_attempts' => 1,
            'sap_submission_error' => 'Transient SAP error',
        ]);
        $this->createBalancedJournalDetails($journal);

        $this->mock(SapService::class, function ($mock) {
            $mock->shouldReceive('createJournalEntry')
                ->once()
                ->andReturn([
                    'success' => true,
                    'journal_number' => 'SAP-9002',
                    'data' => [
                        'DocEntry' => 9002,
                        'Number' => 'SAP-9002',
                        'JdtNum' => 5002,
                    ],
                ]);
        });

        $this->artisan('sap:post-unposted-vj')
            ->assertSuccessful();

        $journal->refresh();

        $this->assertEquals('SAP-9002', $journal->sap_journal_no);
        $this->assertEquals(2, $journal->sap_submission_attempts);
        $this->assertEquals('success', $journal->sap_submission_status);
        $this->assertEquals($user->id, $journal->posted_by);
    }

    public function test_dry_run_does_not_call_sap(): void
    {
        $this->createAutoSubmitUser();
        $journal = $this->createUnpostedJournal();
        $this->createBalancedJournalDetails($journal);

        $sapMock = $this->mock(SapService::class);
        $sapMock->shouldNotReceive('createJournalEntry');

        $this->artisan('sap:post-unposted-vj', ['--dry-run' => true])
            ->assertSuccessful()
            ->expectsOutputToContain('Dry run: 1 candidate(s) would be submitted.');

        $journal->refresh();
        $this->assertNull($journal->sap_journal_no);
    }

    public function test_command_fails_when_auto_submit_user_is_missing(): void
    {
        config(['services.sap.auto_submit_user_id' => 99999]);

        $journal = $this->createUnpostedJournal();
        $this->createBalancedJournalDetails($journal);

        $sapMock = $this->mock(SapService::class);
        $sapMock->shouldNotReceive('createJournalEntry');

        $this->artisan('sap:post-unposted-vj')
            ->assertFailed()
            ->expectsOutputToContain('SAP auto-submit user is not configured or does not exist.');
    }
}
