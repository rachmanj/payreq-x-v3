<?php

namespace App\Http\Requests\UserPayreq;

use App\Models\Anggaran;
use App\Support\AnggaranFormDetails;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ProcessAnggaranRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $rows = Arr::wrap($this->input('details', []));
        $this->merge([
            'amount' => AnggaranFormDetails::sumLineAmounts($rows),
        ]);
    }

    public function authorize(): bool
    {
        $buttonType = $this->input('button_type');

        if (in_array($buttonType, ['edit', 'edit_submit'], true)) {
            $anggaran = Anggaran::find((int) $this->input('anggaran_id'));

            return $anggaran !== null && $this->user()->can('editThroughPayreq', $anggaran);
        }

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'button_type' => ['required', Rule::in(['create', 'create_submit', 'edit', 'edit_submit'])],
            'nomor' => ['required', 'string', 'max:30'],
            'anggaran_id' => [Rule::requiredIf(fn () => in_array($this->input('button_type'), ['edit', 'edit_submit'], true)), 'nullable', 'exists:anggarans,id'],
            'rab_no' => ['nullable', 'string', 'max:255'],
            'date' => ['nullable', 'date'],
            'project' => ['required', 'string', 'exists:projects,code'],
            'description' => ['required', 'string'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'rab_type' => ['required', Rule::in(['periode', 'event', 'buc'])],
            'usage' => ['required', Rule::in(['user', 'department', 'project'])],
            'periode_anggaran' => [Rule::requiredIf(fn () => $this->input('rab_type') === 'periode'), 'nullable', 'date'],
            'start_date' => [Rule::requiredIf(fn () => in_array($this->input('rab_type'), ['event', 'buc'], true)), 'nullable', 'date'],
            'end_date' => [Rule::requiredIf(fn () => in_array($this->input('rab_type'), ['event', 'buc'], true)), 'nullable', 'date', 'after_or_equal:start_date'],
            'file_upload' => ['nullable', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx'],
            'details' => ['required', 'array', 'min:1'],
            'details.*.description' => ['nullable', 'string', 'max:500'],
            'details.*.qty' => ['nullable', 'numeric', 'min:0'],
            'details.*.unit' => ['nullable', 'string', 'max:50'],
            'details.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'details.*.amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'button_type.required' => 'Invalid submit action.',
            'amount.min' => 'Total amount must be greater than zero. Add amounts on budget lines.',
            'file_upload.max' => 'The file may not be greater than 10 MB.',
            'details.required' => 'At least one budget line is required.',
            'details.min' => 'At least one budget line is required.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $rows = Arr::wrap($this->input('details', []));
            $hasMeaningfulLine = false;

            foreach ($rows as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $description = trim((string) ($row['description'] ?? ''));
                $amount = isset($row['amount']) && $row['amount'] !== '' ? (float) $row['amount'] : 0.0;
                $qty = isset($row['qty']) && $row['qty'] !== '' ? (float) $row['qty'] : 0.0;
                $unitPrice = isset($row['unit_price']) && $row['unit_price'] !== '' ? (float) $row['unit_price'] : 0.0;

                if ($description !== '' || $amount > 0 || ($qty > 0 && $unitPrice > 0)) {
                    $hasMeaningfulLine = true;
                    break;
                }
            }

            if (! $hasMeaningfulLine) {
                $validator->errors()->add('details', 'Enter at least one budget line with a description or amount.');
            }
        });
    }
}
