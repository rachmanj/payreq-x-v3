<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreJournalEntryTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create_manual_journal_entry');
    }

    public function rules(): array
    {
        $templateId = $this->route('id') ?? $this->route('template');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('journal_entry_templates', 'name')->ignore($templateId),
            ],
            'description' => 'nullable|string|max:1000',
            'lines' => 'required|array|min:2',
            'lines.*.account_code' => 'required|string|max:50',
            'lines.*.debit_credit' => ['required', Rule::in(['debit', 'credit'])],
            'lines.*.default_amount' => 'nullable|numeric|min:0',
            'lines.*.project' => 'nullable|string|max:20',
            'lines.*.cost_center' => 'nullable|string|max:50',
            'lines.*.description' => 'nullable|string|max:500',
        ];
    }
}
