<?php

namespace App\Services;

use App\Models\JournalEntry;
use App\Models\SapSubmissionLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class JournalEntrySubmissionService
{
    public function __construct(
        protected SapService $sapService
    ) {}

    public function submit(JournalEntry $journalEntry, User $user): array
    {
        $builder = new JournalEntryBuilder($journalEntry);
        $validationErrors = $builder->validate();

        if (! empty($validationErrors)) {
            return [
                'success' => false,
                'message' => 'Validation failed: '.implode(', ', $validationErrors),
            ];
        }

        $attemptNumber = ($journalEntry->sap_submission_attempts ?? 0) + 1;
        $journalEntryData = $builder->build();

        try {
            $result = $this->sapService->createJournalEntry($journalEntryData);
        } catch (\Exception $e) {
            $this->recordFailure($journalEntry, $user, $attemptNumber, $e->getMessage());

            Log::error('SAP B1 manual journal submission failed', [
                'journal_entry_id' => $journalEntry->id,
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
            $this->recordFailure($journalEntry, $user, $attemptNumber, $message);

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
            DB::transaction(function () use ($journalEntry, $user, $attemptNumber, $sapJournalNumber, $sapResponseSummary) {
                $postingDate = Carbon::now()->format('Y-m-d');

                $journalEntry->sap_journal_no = $sapJournalNumber;
                $journalEntry->sap_je_jdt_num = $sapResponseSummary['JdtNum'] ?? $sapResponseSummary['DocEntry'] ?? null;
                $journalEntry->sap_posting_date = $postingDate;
                $journalEntry->sap_submission_status = 'success';
                $journalEntry->sap_submission_attempts = $attemptNumber;
                $journalEntry->sap_submitted_at = Carbon::now();
                $journalEntry->sap_submitted_by = $user->id;
                $journalEntry->sap_submission_error = null;
                $journalEntry->save();

                SapSubmissionLog::create([
                    'journal_entry_id' => $journalEntry->id,
                    'user_id' => $user->id,
                    'submitted_by' => $user->id,
                    'document_type' => 'manual_journal_entry',
                    'status' => 'success',
                    'action' => 'submission',
                    'error_message' => null,
                    'sap_response' => $sapResponseSummary ? json_encode($sapResponseSummary) : null,
                    'sap_journal_number' => $sapJournalNumber,
                    'sap_doc_entry' => $sapResponseSummary['JdtNum'] ?? $sapResponseSummary['DocEntry'] ?? null,
                    'attempt_number' => $attemptNumber,
                ]);
            });
        } catch (\Throwable $e) {
            Log::error('SAP B1 manual journal submission save failed', [
                'journal_entry_id' => $journalEntry->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'An error occurred while saving SAP submission: '.$e->getMessage(),
            ];
        }

        Log::info('SAP B1 manual journal submission successful', [
            'journal_entry_id' => $journalEntry->id,
            'sap_journal_number' => $sapJournalNumber,
            'user_id' => $user->id,
        ]);

        return [
            'success' => true,
            'sap_journal_no' => $sapJournalNumber,
            'message' => 'Journal entry successfully submitted to SAP B1. Journal Number: '.$sapJournalNumber,
        ];
    }

    public function reverse(JournalEntry $journalEntry, User $user, string $reason): array
    {
        if (empty($journalEntry->sap_journal_no)) {
            return [
                'success' => false,
                'message' => 'This journal has not been posted to SAP B1.',
            ];
        }

        if ($journalEntry->isReversed()) {
            return [
                'success' => false,
                'message' => 'This journal has already been reversed.',
            ];
        }

        if (empty($journalEntry->sap_je_jdt_num)) {
            return [
                'success' => false,
                'message' => 'This journal was posted before automatic reversal tracking. Manual reversal is not supported for manual journal entries.',
            ];
        }

        $originalSapJournalNo = $journalEntry->sap_journal_no;

        try {
            $result = $this->sapService->cancelJournalEntry((string) $journalEntry->sap_je_jdt_num);
        } catch (\Exception $e) {
            $this->recordReversalFailure($journalEntry, $user, $originalSapJournalNo, $e->getMessage());

            Log::error('SAP B1 manual journal reversal failed', [
                'journal_entry_id' => $journalEntry->id,
                'sap_je_jdt_num' => $journalEntry->sap_je_jdt_num,
                'error' => $e->getMessage(),
                'user_id' => $user->id,
            ]);

            return [
                'success' => false,
                'message' => 'Failed to reverse journal in SAP B1: '.$e->getMessage(),
            ];
        }

        if (! ($result['success'] ?? false)) {
            $message = $result['message'] ?? 'SAP reversal returned unsuccessful result';
            $this->recordReversalFailure($journalEntry, $user, $originalSapJournalNo, $message);

            return [
                'success' => false,
                'message' => 'Failed to reverse journal in SAP B1: '.$message,
            ];
        }

        try {
            DB::transaction(function () use ($journalEntry, $user, $reason, $originalSapJournalNo, $result) {
                $journalEntry->sap_reversed_at = Carbon::now();
                $journalEntry->sap_reversed_by = $user->id;
                $journalEntry->sap_reversal_reason = $reason;
                $journalEntry->sap_reversal_journal_no = $result['reversal_journal_no'] ?? null;
                $journalEntry->save();

                SapSubmissionLog::create([
                    'journal_entry_id' => $journalEntry->id,
                    'user_id' => $user->id,
                    'submitted_by' => $user->id,
                    'document_type' => 'manual_journal_entry',
                    'status' => 'success',
                    'action' => 'reversal',
                    'error_message' => $reason,
                    'sap_response' => null,
                    'sap_journal_number' => $originalSapJournalNo,
                    'sap_doc_num' => $result['reversal_journal_no'] ?? null,
                    'attempt_number' => 1,
                ]);
            });
        } catch (\Throwable $e) {
            Log::error('SAP B1 manual journal reversal save failed', [
                'journal_entry_id' => $journalEntry->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Journal was cancelled in SAP B1 but failed to update local records: '.$e->getMessage(),
            ];
        }

        Log::info('SAP B1 manual journal reversal successful', [
            'journal_entry_id' => $journalEntry->id,
            'original_sap_journal_no' => $originalSapJournalNo,
            'reversal_journal_no' => $result['reversal_journal_no'] ?? null,
            'user_id' => $user->id,
        ]);

        return [
            'success' => true,
            'message' => 'Journal successfully reversed in SAP B1. Original Journal Number: '.$originalSapJournalNo,
        ];
    }

    public function recordFailure(JournalEntry $journalEntry, User $user, int $attemptNumber, string $errorMessage): void
    {
        DB::transaction(function () use ($journalEntry, $user, $attemptNumber, $errorMessage) {
            $journalEntry->sap_submission_status = 'failed';
            $journalEntry->sap_submission_attempts = $attemptNumber;
            $journalEntry->sap_submission_error = $errorMessage;
            $journalEntry->sap_submitted_at = Carbon::now();
            $journalEntry->sap_submitted_by = $user->id;
            $journalEntry->save();

            SapSubmissionLog::create([
                'journal_entry_id' => $journalEntry->id,
                'user_id' => $user->id,
                'submitted_by' => $user->id,
                'document_type' => 'manual_journal_entry',
                'status' => 'failed',
                'action' => 'submission',
                'error_message' => $errorMessage,
                'sap_error' => $errorMessage,
                'sap_response' => null,
                'sap_journal_number' => null,
                'attempt_number' => $attemptNumber,
            ]);
        });
    }

    protected function recordReversalFailure(JournalEntry $journalEntry, User $user, string $originalSapJournalNo, string $errorMessage): void
    {
        SapSubmissionLog::create([
            'journal_entry_id' => $journalEntry->id,
            'user_id' => $user->id,
            'submitted_by' => $user->id,
            'document_type' => 'manual_journal_entry',
            'status' => 'failed',
            'action' => 'reversal',
            'error_message' => $errorMessage,
            'sap_error' => $errorMessage,
            'sap_journal_number' => $originalSapJournalNo,
            'attempt_number' => 1,
        ]);
    }
}
