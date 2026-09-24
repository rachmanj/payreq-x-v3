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

    protected function prepareForValidation(): void
    {
        $lines = $this->input('lines', []);

        if (! is_array($lines)) {
            return;
        }

        foreach ($lines as $index => $line) {
            if (! is_array($line)) {
                continue;
            }

            if (array_key_exists('project', $line) && is_string($line['project'])) {
                $lines[$index]['project'] = trim($line['project']);
            }

            if (array_key_exists('cost_center', $line) && is_string($line['cost_center'])) {
                $lines[$index]['cost_center'] = trim($line['cost_center']);
            }
        }

        $this->merge([
            'lines' => $lines,
        ]);
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
            'lines.*.project' => 'required|string|max:50',
            'lines.*.cost_center' => 'required|string|max:50',
            'lines.*.description' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'lines.*.project.required' => 'The project field is required for line :position.',
            'lines.*.cost_center.required' => 'Cost center is required for line :position.',
        ];
    }
}
