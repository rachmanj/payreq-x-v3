<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VerificationJournal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackfillPostedVjValidationStatusCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_updates_posted_journals_with_pending_validation(): void
    {
        $poster = User::factory()->create();
        $submittedAt = now()->subDay();

        $journal = VerificationJournal::query()->create([
            'nomor' => 'VJ-BACKFILL-1',
            'date' => now()->toDateString(),
            'project' => '021C',
            'amount' => 1000,
            'created_by' => $poster->id,
            'sap_journal_no' => '267674600',
            'validation_status' => VerificationJournal::VALIDATION_PENDING,
            'posted_by' => $poster->id,
            'sap_submitted_at' => $submittedAt,
        ]);

        $this->artisan('vj:backfill-posted-validation')
            ->assertSuccessful()
            ->expectsOutputToContain('Updated 1 verification journal(s).');

        $journal->refresh();

        $this->assertSame(VerificationJournal::VALIDATION_VALIDATED, $journal->validation_status);
        $this->assertEquals($poster->id, $journal->validated_by);
        $this->assertNotNull($journal->validated_at);
    }

    public function test_command_does_not_update_already_validated_journals(): void
    {
        $validator = User::factory()->create();

        VerificationJournal::query()->create([
            'nomor' => 'VJ-ALREADY-VALID',
            'date' => now()->toDateString(),
            'project' => '021C',
            'amount' => 1000,
            'created_by' => $validator->id,
            'sap_journal_no' => '267674601',
            'validation_status' => VerificationJournal::VALIDATION_VALIDATED,
            'validated_by' => $validator->id,
            'validated_at' => now()->subDays(2),
            'posted_by' => $validator->id,
            'sap_submitted_at' => now()->subDay(),
        ]);

        $this->artisan('vj:backfill-posted-validation')
            ->assertSuccessful()
            ->expectsOutput('No posted verification journals require validation status backfill.');
    }

    public function test_dry_run_lists_candidates_without_updating(): void
    {
        $poster = User::factory()->create();

        $journal = VerificationJournal::query()->create([
            'nomor' => 'VJ-DRY-RUN',
            'date' => now()->toDateString(),
            'project' => '021C',
            'amount' => 1000,
            'created_by' => $poster->id,
            'sap_journal_no' => '267674755',
            'validation_status' => VerificationJournal::VALIDATION_PENDING,
            'posted_by' => $poster->id,
            'sap_submitted_at' => now()->subDay(),
        ]);

        $this->artisan('vj:backfill-posted-validation', ['--dry-run' => true])
            ->assertSuccessful()
            ->expectsOutputToContain('Dry run: 1 journal(s) would be updated.');

        $journal->refresh();
        $this->assertSame(VerificationJournal::VALIDATION_PENDING, $journal->validation_status);
    }
}
