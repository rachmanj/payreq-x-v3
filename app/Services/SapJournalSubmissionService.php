<?php

namespace App\Services;

use App\Models\Incoming;
use App\Models\Realization;
use App\Models\SapSubmissionLog;
use App\Models\User;
use App\Models\VerificationJournal;
use App\Models\VerificationJournalDetail;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SapJournalSubmissionService
{
    public function __construct(
        protected SapService $sapService
    ) {}

    public function submit(VerificationJournal $vj, User $user): array
    {
        $builder = new SapJournalEntryBuilder($vj);
        $validationErrors = $builder->validate();

        if (! empty($validationErrors)) {
            return [
                'success' => false,
                'message' => 'Validation failed: '.implode(', ', $validationErrors),
            ];
        }

        $attemptNumber = ($vj->sap_submission_attempts ?? 0) + 1;
        $journalEntryData = $builder->build();

        try {
            $result = $this->sapService->createJournalEntry($journalEntryData);
        } catch (\Exception $e) {
            $this->recordFailure($vj, $user, $attemptNumber, $e->getMessage());

            Log::error('SAP B1 journal submission failed', [
                'verification_journal_id' => $vj->id,
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'attempt_number' => $attemptNumber,
            ]);

            return [
                'success' => false,
                'message' => 'Failed to submit to SAP B1: '.$e->getMessage().'. Please check the error and try again.',
            ];
        }

        if (! ($result['success'] ?? false)) {
            $message = $result['message'] ?? 'SAP submission returned unsuccessful result';

            $this->recordFailure($vj, $user, $attemptNumber, $message);

            return [
                'success' => false,
                'message' => 'Failed to submit to SAP B1: '.$message,
            ];
        }

        $sapJournalNumber = $result['journal_number'] ?? $result['doc_entry'] ?? null;
        $sapResponse = $result['data'] ?? null;

        $sapResponseSummary = null;
        if ($sapResponse && is_array($sapResponse)) {
            $sapResponseSummary = [
                'DocEntry' => $sapResponse['DocEntry'] ?? null,
                'DocNum' => $sapResponse['DocNum'] ?? null,
                'Number' => $sapResponse['Number'] ?? null,
                'JdtNum' => $sapResponse['JdtNum'] ?? null,
                'TransId' => $sapResponse['TransId'] ?? null,
                'ReferenceDate' => $sapResponse['ReferenceDate'] ?? null,
                'TaxDate' => $sapResponse['TaxDate'] ?? null,
                'Memo' => $sapResponse['Memo'] ?? null,
                'LineCount' => isset($sapResponse['JournalEntryLines']) ? count($sapResponse['JournalEntryLines']) : 0,
            ];
        }

        try {
            DB::transaction(function () use ($vj, $user, $attemptNumber, $sapJournalNumber, $sapResponseSummary) {
                $postingDate = Carbon::now()->format('Y-m-d');

                $vj->sap_journal_no = $sapJournalNumber;
                $vj->sap_je_jdt_num = $sapResponseSummary['JdtNum'] ?? $sapResponseSummary['DocEntry'] ?? null;
                $vj->sap_posting_date = $postingDate;
                $vj->posted_by = $user->id;
                $vj->sap_submission_status = 'success';
                $vj->sap_submission_attempts = $attemptNumber;
                $vj->sap_submitted_at = Carbon::now();
                $vj->sap_submitted_by = $user->id;
                $vj->sap_submission_error = null;
                $vj->save();

                $vjDetails = VerificationJournalDetail::where('verification_journal_id', $vj->id)->get();
                foreach ($vjDetails as $detail) {
                    $detail->sap_journal_no = $sapJournalNumber;
                    $detail->save();
                }

                SapSubmissionLog::create([
                    'verification_journal_id' => $vj->id,
                    'user_id' => $user->id,
                    'status' => 'success',
                    'action' => 'submission',
                    'error_message' => null,
                    'sap_response' => $sapResponseSummary ? json_encode($sapResponseSummary) : null,
                    'sap_journal_number' => $sapJournalNumber,
                    'attempt_number' => $attemptNumber,
                ]);

                if ($vj->type === 'bank') {
                    $incoming = Incoming::where('nomor', $vj->nomor)->first();
                    if ($incoming) {
                        $incoming->sap_journal_no = $sapJournalNumber;
                        $incoming->save();
                    }
                    $vj->status = 'posted';
                    $vj->save();
                }

                $realizations = Realization::whereIn('nomor', $vjDetails->pluck('realization_no')->toArray())->get();
                foreach ($realizations as $realization) {
                    $realization->status = 'close';
                    $realization->save();
                }
            });
        } catch (\Throwable $e) {
            Log::error('SAP B1 journal submission exception', [
                'verification_journal_id' => $vj->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'An error occurred while saving SAP submission: '.$e->getMessage(),
            ];
        }

        Log::info('SAP B1 journal submission successful', [
            'verification_journal_id' => $vj->id,
            'sap_journal_number' => $sapJournalNumber,
            'user_id' => $user->id,
        ]);

        return [
            'success' => true,
            'sap_journal_no' => $sapJournalNumber,
            'message' => 'Journal entry successfully submitted to SAP B1. Journal Number: '.$sapJournalNumber,
        ];
    }

    public function recordFailure(VerificationJournal $vj, User $user, int $attemptNumber, string $errorMessage): void
    {
        DB::transaction(function () use ($vj, $user, $attemptNumber, $errorMessage) {
            $vj->sap_submission_status = 'failed';
            $vj->sap_submission_attempts = $attemptNumber;
            $vj->sap_submission_error = $errorMessage;
            $vj->sap_submitted_at = Carbon::now();
            $vj->sap_submitted_by = $user->id;
            $vj->save();

            SapSubmissionLog::create([
                'verification_journal_id' => $vj->id,
                'user_id' => $user->id,
                'status' => 'failed',
                'action' => 'submission',
                'error_message' => $errorMessage,
                'sap_response' => null,
                'sap_journal_number' => null,
                'attempt_number' => $attemptNumber,
            ]);
        });
    }
}
