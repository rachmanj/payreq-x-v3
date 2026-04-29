<?php

namespace App\Http\Requests\UserPayreq;

use App\Models\Anggaran;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProcessAnggaranRequest extends FormRequest
{
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
            'periode_anggaran' => [Rule::requiredIf(fn () => $this->input('rab_type') === 'periode'), 'nullable', 'date'],
            'start_date' => [Rule::requiredIf(fn () => in_array($this->input('rab_type'), ['event', 'buc'], true)), 'nullable', 'date'],
            'end_date' => [Rule::requiredIf(fn () => in_array($this->input('rab_type'), ['event', 'buc'], true)), 'nullable', 'date', 'after_or_equal:start_date'],
            'file_upload' => ['nullable', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'button_type.required' => 'Invalid submit action.',
            'amount.min' => 'Amount must be greater than zero.',
            'file_upload.max' => 'The file may not be greater than 10 MB.',
        ];
    }
}
