<?php

namespace App\Console\Commands;

use App\Models\VerificationJournal;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BackfillPostedVjValidationStatus extends Command
{
    protected $signature = 'vj:backfill-posted-validation
        {--dry-run : List affected journals without updating}';

    protected $description = 'Set validation_status to validated for posted VJs that were auto-submitted without validation.';

    public function handle(): int
    {
        $candidates = VerificationJournal::query()
            ->whereNotNull('sap_journal_no')
            ->where('validation_status', '!=', VerificationJournal::VALIDATION_VALIDATED)
            ->orderBy('id')
            ->get(['id', 'nomor', 'project', 'sap_journal_no', 'validation_status', 'sap_submitted_at', 'posted_by']);

        if ($candidates->isEmpty()) {
            $this->info('No posted verification journals require validation status backfill.');

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->info('Dry run: '.$candidates->count().' journal(s) would be updated.');
            $this->table(
                ['ID', 'Nomor', 'Project', 'SAP Journal No', 'Current Status', 'Posted At'],
                $candidates->map(fn (VerificationJournal $vj) => [
                    $vj->id,
                    $vj->nomor,
                    $vj->project,
                    $vj->sap_journal_no,
                    $vj->validation_status,
                    $vj->sap_submitted_at?->format('Y-m-d H:i:s') ?? '-',
                ])->all()
            );

            return self::SUCCESS;
        }

        $updated = 0;

        DB::transaction(function () use ($candidates, &$updated) {
            foreach ($candidates as $journal) {
                $journal->update([
                    'validation_status' => VerificationJournal::VALIDATION_VALIDATED,
                    'validated_at' => $journal->sap_submitted_at ?? $journal->updated_at,
                    'validated_by' => $journal->posted_by,
                    'rejection_reason' => null,
                ]);

                $updated++;
            }
        });

        Log::info('vj:backfill-posted-validation completed', [
            'updated' => $updated,
            'journal_ids' => $candidates->pluck('id')->all(),
        ]);

        $this->info("Backfill completed. Updated {$updated} verification journal(s).");

        return self::SUCCESS;
    }
}
