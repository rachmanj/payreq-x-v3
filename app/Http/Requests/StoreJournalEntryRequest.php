<?php

namespace App\Http\Requests;

use App\Services\JournalEntryMulticurrencyService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreJournalEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create_manual_journal_entry');
    }

    protected function prepareForValidation(): void
    {
        $multicurrencyService = app(JournalEntryMulticurrencyService::class);
        $lines = $this->input('lines', []);

        if (is_array($lines)) {
            $lines = $multicurrencyService->normalizeLines($lines);

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
            'lines.*.currency' => ['nullable', Rule::in(['IDR', 'USD'])],
            'lines.*.amount' => 'nullable|numeric|min:0',
            'lines.*.fc_amount' => 'nullable|numeric|min:0',
            'lines.*.exchange_rate' => 'nullable|numeric|min:0',
            'lines.*.project' => 'required|string|max:50',
            'lines.*.cost_center' => 'required|string|max:50',
            'lines.*.description' => 'nullable|string|max:500',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $lines = $this->input('lines', []);
            $multicurrencyService = app(JournalEntryMulticurrencyService::class);

            foreach ($multicurrencyService->validateLines($lines) as $message) {
                $validator->errors()->add('lines', $message);
            }
        });
    }

    public function messages(): array
    {
        return [
            'lines.*.currency.in' => 'Baris valas hanya mendukung USD untuk saat ini',
            'lines.*.project.required' => 'The project field is required for line :position.',
            'lines.*.cost_center.required' => 'Cost center is required for line :position.',
        ];
    }
}
