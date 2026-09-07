<?php

namespace App\Http\Requests;

use App\Models\BpjsApInvoice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBpjsApInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('submit_sap_ap_invoice_bpjs') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'jenis' => ['required', Rule::in([
                BpjsApInvoice::JENIS_KESEHATAN,
                BpjsApInvoice::JENIS_KETENAGAKERJAAN,
            ])],
            'unit' => ['required', 'string', 'max:20'],
            'periode' => ['required', 'date_format:Y-m'],
            'amount' => ['required', 'numeric', 'min:1'],
            'doc_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:doc_date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'jenis.required' => 'Jenis BPJS wajib dipilih.',
            'jenis.in' => 'Jenis BPJS tidak valid.',
            'unit.required' => 'Unit wajib dipilih.',
            'periode.required' => 'Periode wajib diisi.',
            'periode.date_format' => 'Format periode harus YYYY-MM.',
            'amount.required' => 'Nominal wajib diisi.',
            'amount.min' => 'Nominal harus lebih dari nol.',
            'doc_date.required' => 'Tanggal dokumen wajib diisi.',
            'due_date.required' => 'Tanggal jatuh tempo wajib diisi.',
            'due_date.after_or_equal' => 'Tanggal jatuh tempo harus sama atau setelah tanggal dokumen.',
        ];
    }
}
