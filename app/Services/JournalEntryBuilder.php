<?php

namespace App\Services;

use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Carbon\Carbon;

class JournalEntryBuilder
{
    protected JournalEntry $journalEntry;

    protected $journalLines;

    protected JournalEntryMulticurrencyService $multicurrencyService;

    public function __construct(
        JournalEntry $journalEntry,
        ?JournalEntryMulticurrencyService $multicurrencyService = null,
    ) {
        $this->journalEntry = $journalEntry;
        $this->multicurrencyService = $multicurrencyService ?? app(JournalEntryMulticurrencyService::class);
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

            $currency = $this->multicurrencyService->resolveCurrency([
                'currency' => $detail->currency ?? 'IDR',
            ]);

            if ($detail->debit_credit === 'debit') {
                $line['Debit'] = (float) $detail->amount;
                $line['Credit'] = 0.0;
            } else {
                $line['Debit'] = 0.0;
                $line['Credit'] = (float) $detail->amount;
            }

            if ($currency === JournalEntryMulticurrencyService::SUPPORTED_FOREIGN_CURRENCY) {
                $line['FCCurrency'] = JournalEntryMulticurrencyService::SUPPORTED_FOREIGN_CURRENCY;
                $fcAmount = (float) $detail->fc_amount;

                if ($detail->debit_credit === 'debit') {
                    $line['FCDebit'] = $fcAmount;
                    $line['FCCredit'] = 0.0;
                } else {
                    $line['FCDebit'] = 0.0;
                    $line['FCCredit'] = $fcAmount;
                }
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

        foreach ($this->journalLines as $detail) {
            if (empty($detail->account_code)) {
                $errors[] = 'One or more journal details have missing account code';
                break;
            }
        }

        $multicurrencyErrors = $this->multicurrencyService->validateLines($this->journalLines);
        $errors = array_merge($errors, $multicurrencyErrors);

        return $errors;
    }
}
