<?php

namespace App\Services;

use App\Models\VerificationJournal;
use App\Models\VerificationJournalDetail;
use App\Models\Account;
use Carbon\Carbon;

class SapJournalEntryBuilder
{
    protected VerificationJournal $verificationJournal;
    protected $journalDetails;

    public function __construct(VerificationJournal $verificationJournal)
    {
        $this->verificationJournal = $verificationJournal;
        $this->journalDetails = VerificationJournalDetail::where('verification_journal_id', $verificationJournal->id)->get();
    }

    public function build(): array
    {
        $referenceDate = Carbon::parse($this->verificationJournal->date)->format('Y-m-d');
        $taxDate = $referenceDate;
        $dueDate = $referenceDate;

        $lines = [];
        $lineId = 0;

        foreach ($this->journalDetails as $detail) {
            $line = [
                'AccountCode' => $detail->account_code,
                'LineMemo' => $detail->description ?? '',
                'DueDate' => $dueDate,
            ];

            if ($detail->debit_credit === 'debit') {
                $line['Debit'] = (float) $detail->amount;
                $line['Credit'] = 0.0;
            } else {
                $line['Debit'] = 0.0;
                $line['Credit'] = (float) $detail->amount;
            }

            if ($detail->project) {
                $line['ProjectCode'] = $detail->project;
            }

            if ($detail->cost_center) {
                $line['CostingCode'] = $detail->cost_center;
            }

            $lines[] = $line;
        }

        $journalEntry = [
            'ReferenceDate' => $referenceDate,
            'TaxDate' => $taxDate,
            'Memo' => $this->verificationJournal->description ?? 'Verification Journal: ' . $this->verificationJournal->nomor,
            'JournalEntryLines' => $lines,
        ];

        return $journalEntry;
    }

    public function validate(): array
    {
        $errors = [];

        if ($this->journalDetails->isEmpty()) {
            $errors[] = 'Journal entry has no details';
        }

        $totalDebit = $this->journalDetails->where('debit_credit', 'debit')->sum('amount');
        $totalCredit = $this->journalDetails->where('debit_credit', 'credit')->sum('amount');

        if (abs($totalDebit - $totalCredit) > 0.01) {
            $errors[] = sprintf(
                'Journal entry is not balanced. Debit: %s, Credit: %s, Difference: %s',
                number_format($totalDebit, 2),
                number_format($totalCredit, 2),
                number_format(abs($totalDebit - $totalCredit), 2)
            );
        }

        foreach ($this->journalDetails as $detail) {
            if (empty($detail->account_code)) {
                $errors[] = 'One or more journal details have missing account code';
                break;
            }
        }

        return $errors;
    }
}

