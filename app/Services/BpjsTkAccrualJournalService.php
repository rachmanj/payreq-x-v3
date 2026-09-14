<?php

namespace App\Services;

use App\Models\BpjsApInvoice;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class BpjsTkAccrualJournalService
{
    public function __construct(
        protected JournalEntrySubmissionService $submissionService
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function createAndSubmit(BpjsApInvoice $invoice, User $user): array
    {
        $invoice->refresh();

        if ($invoice->jenis !== BpjsApInvoice::JENIS_KETENAGAKERJAAN) {
            return [
                'success' => true,
                'skipped' => true,
                'message' => 'Accrual journal only applies to BPJS Ketenagakerjaan.',
            ];
        }

        if (! $invoice->auto_je) {
            if ($invoice->je_status !== BpjsApInvoice::JE_STATUS_SKIPPED) {
                $invoice->update(['je_status' => BpjsApInvoice::JE_STATUS_SKIPPED]);
            }

            return [
                'success' => true,
                'skipped' => true,
                'message' => 'Accrual journal skipped (auto_je disabled).',
            ];
        }

        if ($invoice->journal_entry_id && $invoice->je_status === BpjsApInvoice::JE_STATUS_SUCCESS) {
            return [
                'success' => true,
                'message' => 'Accrual journal already posted.',
                'journal_entry_id' => $invoice->journal_entry_id,
            ];
        }

        $postingDate = $invoice->je_posting_date ?? BpjsApInvoice::defaultJePostingDate($invoice->periode);

        if (! $invoice->je_posting_date) {
            $invoice->update(['je_posting_date' => $postingDate]);
        }

        $journalEntry = $invoice->journal_entry_id
            ? JournalEntry::query()->findOrFail($invoice->journal_entry_id)
            : $this->createJournalEntry($invoice, $user, $postingDate);

        if (! $invoice->journal_entry_id) {
            $invoice->update(['journal_entry_id' => $journalEntry->id]);
        }

        $result = $this->submissionService->submit($journalEntry, $user);

        $invoice->update([
            'je_status' => ($result['success'] ?? false)
                ? BpjsApInvoice::JE_STATUS_SUCCESS
                : BpjsApInvoice::JE_STATUS_FAILED,
            'je_error' => ($result['success'] ?? false) ? null : ($result['message'] ?? 'Unknown error'),
            'je_submitted_at' => now(),
            'je_submitted_by' => $user->id,
        ]);

        return array_merge($result, [
            'journal_entry_id' => $journalEntry->id,
        ]);
    }

    protected function createJournalEntry(BpjsApInvoice $invoice, User $user, string $postingDate): JournalEntry
    {
        return DB::transaction(function () use ($invoice, $user, $postingDate) {
            $entry = JournalEntry::create([
                'number' => 'TEMP',
                'date' => $postingDate,
                'memo' => $this->buildMemo($invoice),
                'reference' => $invoice->invoiceNumber(),
                'created_by' => $user->id,
                'sap_submission_status' => 'pending',
            ]);

            $entry->update([
                'number' => 'JE-'.str_pad((string) $entry->id, 6, '0', STR_PAD_LEFT),
            ]);

            $amount = (float) $invoice->amount;
            $description = $invoice->label;
            $expenseAccount = BpjsApInvoice::EXPENSE_ACCOUNT_CODES[BpjsApInvoice::JENIS_KETENAGAKERJAAN];

            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'line_no' => 1,
                'account_code' => $expenseAccount,
                'debit_credit' => 'debit',
                'amount' => $amount,
                'project' => $invoice->unit,
                'cost_center' => '20',
                'description' => $description,
            ]);

            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'line_no' => 2,
                'account_code' => BpjsApInvoice::ACCRUAL_ACCOUNT_CODE,
                'debit_credit' => 'credit',
                'amount' => $amount,
                'project' => $invoice->unit,
                'cost_center' => '20',
                'description' => $description,
            ]);

            return $entry->fresh();
        });
    }

    protected function buildMemo(BpjsApInvoice $invoice): string
    {
        return 'Jurnal Akrual '.$invoice->label;
    }
}
