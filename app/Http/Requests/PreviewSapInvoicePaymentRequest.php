<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PreviewSapInvoicePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('submit_sap_invoice_payment') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'invoice_number' => 'required|string|max:100',
            'supplier_sap_code' => 'required|string|max:50',
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'nullable|date|date_format:Y-m-d',
            'remarks' => 'nullable|string|max:1000',
            'sap_doc' => 'nullable|string|max:50',
        ];
    }
}
