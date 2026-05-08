<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ManualMatchBankReconciliationRequest extends FormRequest
{
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
            'bank_statement_line_id' => ['required', 'integer', 'exists:bank_statement_lines,id'],
            'sap_gl_line_id' => ['required', 'integer', 'exists:sap_gl_lines,id'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $reconciliation = $this->route('bank_reconciliation');
            if ($reconciliation === null || ! $reconciliation instanceof \App\Models\BankReconciliation) {
                return;
            }

            $bankLine = \App\Models\BankStatementLine::query()->find((int) $this->input('bank_statement_line_id'));
            $sapLine = \App\Models\SapGlLine::query()->find((int) $this->input('sap_gl_line_id'));

            if ($bankLine === null || $sapLine === null) {
                return;
            }

            if ((int) $bankLine->bank_reconciliation_id !== (int) $reconciliation->id) {
                $validator->errors()->add('bank_statement_line_id', 'Line does not belong to this reconciliation.');
            }

            if ((int) $sapLine->bank_reconciliation_id !== (int) $reconciliation->id) {
                $validator->errors()->add('sap_gl_line_id', 'Line does not belong to this reconciliation.');
            }

            if ($bankLine->matched_status !== \App\Models\BankStatementLine::MATCH_UNMATCHED) {
                $validator->errors()->add('bank_statement_line_id', 'Bank line is already matched.');
            }

            if ($sapLine->matched_status !== \App\Models\SapGlLine::MATCH_UNMATCHED) {
                $validator->errors()->add('sap_gl_line_id', 'SAP line is already matched.');
            }
        });
    }
}
