<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreJournalEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create_manual_journal_entry');
    }

    public function rules(): array
    {
        return [
            'date' => 'required|date',
            'memo' => 'nullable|string|max:1000',
            'reference' => 'nullable|string|max:255',
            'journal_entry_template_id' => 'nullable|exists:journal_entry_templates,id',
            'lines' => 'required|array|min:2',
            'lines.*.account_code' => 'required|string|max:50',
            'lines.*.debit_credit' => ['required', Rule::in(['debit', 'credit'])],
            'lines.*.amount' => 'required|numeric|min:0.01',
            'lines.*.project' => 'nullable|string|max:20',
            'lines.*.cost_center' => 'nullable|string|max:50',
            'lines.*.description' => 'nullable|string|max:500',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $lines = $this->input('lines', []);

            $totalDebit = collect($lines)
                ->where('debit_credit', 'debit')
                ->sum(fn ($line) => (float) ($line['amount'] ?? 0));

            $totalCredit = collect($lines)
                ->where('debit_credit', 'credit')
                ->sum(fn ($line) => (float) ($line['amount'] ?? 0));

            if (abs($totalDebit - $totalCredit) > 0.01) {
                $validator->errors()->add(
                    'lines',
                    sprintf(
                        'Journal entry is not balanced. Debit: %s, Credit: %s, Difference: %s',
                        number_format($totalDebit, 2),
                        number_format($totalCredit, 2),
                        number_format(abs($totalDebit - $totalCredit), 2)
                    )
                );
            }
        });
    }
}
