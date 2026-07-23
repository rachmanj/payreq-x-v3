<?php

namespace App\Services;

use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Carbon\Carbon;

class JournalEntryBuilder
{
    protected JournalEntry $journalEntry;

    protected $journalLines;

    public function __construct(JournalEntry $journalEntry)
    {
        $this->journalEntry = $journalEntry;
        $this->journalLines = JournalEntryLine::where('journal_entry_id', $journalEntry->id)->orderBy('line_no')->get();
    }

    public function build(): array
    {
        $referenceDate = Carbon::parse($this->journalEntry->date)->format('Y-m-d');
        $taxDate = $referenceDate;
        $dueDate = $referenceDate;

        $lines = [];

        foreach ($this->journalLines as $detail) {
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

            $line['Reference1'] = $this->journalEntry->reference ?? '';
            $line['Reference2'] = $this->journalEntry->number ?? '';

            $lines[] = $line;
        }

        return [
            'ReferenceDate' => $referenceDate,
            'TaxDate' => $taxDate,
            'Memo' => $this->journalEntry->memo ?? 'Manual Journal Entry: '.$this->journalEntry->number,
            'JournalEntryLines' => $lines,
        ];
    }

    public function validate(): array
    {
        $errors = [];

        if ($this->journalLines->isEmpty()) {
            $errors[] = 'Journal entry has no details';
        }

        $totalDebit = $this->journalLines->where('debit_credit', 'debit')->sum('amount');
        $totalCredit = $this->journalLines->where('debit_credit', 'credit')->sum('amount');

        if (abs($totalDebit - $totalCredit) > 0.01) {
            $errors[] = sprintf(
                'Journal entry is not balanced. Debit: %s, Credit: %s, Difference: %s',
                number_format($totalDebit, 2),
                number_format($totalCredit, 2),
                number_format(abs($totalDebit - $totalCredit), 2)
            );
        }

        foreach ($this->journalLines as $detail) {
            if (empty($detail->account_code)) {
                $errors[] = 'One or more journal details have missing account code';
                break;
            }
        }

        return $errors;
    }
}
