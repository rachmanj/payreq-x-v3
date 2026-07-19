<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\VerificationJournal;
use App\Services\SapJournalSubmissionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PostUnpostedVerificationJournalsToSap extends Command
{
    protected $signature = 'sap:post-unposted-vj
        {--limit=100 : Max number of journals to process in one run}
        {--dry-run : List candidates without submitting}';

    protected $description = 'Post unposted Verification Journals to SAP B1 (used by the scheduler).';

    public function __construct(
        protected SapJournalSubmissionService $journalSubmissionService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $userId = config('services.sap.auto_submit_user_id');
        $user = $userId ? User::find($userId) : null;

        if (! $user) {
            $message = 'SAP auto-submit user is not configured or does not exist. Set SAP_AUTO_SUBMIT_USER_ID in .env.';
            $this->error($message);
            Log::error('sap:post-unposted-vj aborted', [
                'configured_user_id' => $userId,
                'reason' => 'missing_or_invalid_user',
            ]);

            return self::FAILURE;
        }

        $candidates = $this->candidateQuery((int) $this->option('limit'))->get();

        if ($candidates->isEmpty()) {
            $this->info('No unposted verification journals found for automated SAP submission.');

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->info('Dry run: '.$candidates->count().' candidate(s) would be submitted.');
            $this->table(
                ['ID', 'Nomor', 'Project', 'Date', 'Amount', 'Attempts', 'Status'],
                $candidates->map(fn (VerificationJournal $vj) => [
                    $vj->id,
                    $vj->nomor,
                    $vj->project,
                    $vj->date,
                    number_format((float) $vj->amount, 2),
                    $vj->sap_submission_attempts ?? 0,
                    $vj->sap_submission_status ?? 'none',
                ])->all()
            );

            return self::SUCCESS;
        }

        $success = [];
        $failed = [];

        foreach ($candidates as $journal) {
            $result = $this->journalSubmissionService->submit($journal, $user);

            if ($result['success']) {
                $success[] = [
                    'nomor' => $journal->nomor,
                    'sap_journal_no' => $result['sap_journal_no'] ?? 'N/A',
                ];
                $this->info('Posted '.$journal->nomor.' → '.($result['sap_journal_no'] ?? 'N/A'));

                continue;
            }

            $failed[] = [
                'nomor' => $journal->nomor,
                'message' => $result['message'],
            ];
            $this->error('Failed '.$journal->nomor.': '.$result['message']);
        }

        $summary = [
            'candidates' => $candidates->count(),
            'success' => count($success),
            'failed' => count($failed),
            'user_id' => $user->id,
        ];

        Log::info('sap:post-unposted-vj completed', $summary);

        $this->newLine();
        $this->info('Automated SAP submission completed. Success: '.count($success).', Failed: '.count($failed).'.');

        return self::SUCCESS;
    }

    protected function candidateQuery(int $limit)
    {
        return VerificationJournal::query()
            ->whereNull('sap_journal_no')
            ->where('date', '>=', now()->subDays(30)->toDateString())
            ->where(function ($query) {
                $query->whereNull('sap_submission_status')
                    ->orWhere('sap_submission_status', '!=', 'failed')
                    ->orWhere('sap_submission_attempts', '<', 2);
            })
            ->orderBy('date')
            ->limit($limit);
    }
}
