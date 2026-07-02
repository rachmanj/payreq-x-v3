<?php

namespace App\Http\Requests;

use App\Models\BankReconciliation;
use App\Models\BankStatementLine;
use App\Models\SapGlLine;
use Illuminate\Foundation\Http\FormRequest;

class ManualMatchGroupBankReconciliationRequest extends FormRequest
{
    private const TOTAL_TOLERANCE = 0.005;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'bank_statement_line_ids' => ['required', 'array', 'min:1'],
            'bank_statement_line_ids.*' => ['integer', 'distinct', 'exists:bank_statement_lines,id'],
            'sap_gl_line_ids' => ['required', 'array', 'min:1'],
            'sap_gl_line_ids.*' => ['integer', 'distinct', 'exists:sap_gl_lines,id'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $reconciliation = $this->route('bank_reconciliation');
            if ($reconciliation === null || ! $reconciliation instanceof BankReconciliation) {
                return;
            }

            $bankIds = array_map('intval', $this->input('bank_statement_line_ids', []));
            $sapIds = array_map('intval', $this->input('sap_gl_line_ids', []));

            $bankLines = BankStatementLine::query()->whereIn('id', $bankIds)->get();
            $sapLines = SapGlLine::query()->whereIn('id', $sapIds)->get();

            if ($bankLines->count() !== count(array_unique($bankIds)) || $sapLines->count() !== count(array_unique($sapIds))) {
                $validator->errors()->add('bank_statement_line_ids', 'Invalid line selection.');

                return;
            }

            foreach ($bankLines as $line) {
                if ((int) $line->bank_reconciliation_id !== (int) $reconciliation->id) {
                    $validator->errors()->add('bank_statement_line_ids', 'A bank line does not belong to this reconciliation.');

                    return;
                }
                if ($line->matched_status !== BankStatementLine::MATCH_UNMATCHED) {
                    $validator->errors()->add('bank_statement_line_ids', 'A bank line is already matched.');

                    return;
                }
            }

            foreach ($sapLines as $line) {
                if ((int) $line->bank_reconciliation_id !== (int) $reconciliation->id) {
                    $validator->errors()->add('sap_gl_line_ids', 'An SAP line does not belong to this reconciliation.');

                    return;
                }
                if ($line->matched_status !== SapGlLine::MATCH_UNMATCHED) {
                    $validator->errors()->add('sap_gl_line_ids', 'An SAP line is already matched.');

                    return;
                }
            }

            $bankTotal = $bankLines->sum(fn (BankStatementLine $l) => (float) $l->debit - (float) $l->credit);
            $sapTotal = $sapLines->sum(fn (SapGlLine $l) => (float) $l->debit - (float) $l->credit);

            if (abs($bankTotal + $sapTotal) >= self::TOTAL_TOLERANCE) {
                $validator->errors()->add(
                    'sap_gl_line_ids',
                    'Bank net ('.number_format($bankTotal, 2).') and SAP net ('.number_format($sapTotal, 2).') must offset to zero (sum within '.self::TOTAL_TOLERANCE.'). Current sum: '.number_format($bankTotal + $sapTotal, 2).'.'
                );
            }
        });
    }
}
