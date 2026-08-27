<?php

namespace App\Http\Requests;

use App\Services\SapVendorPaymentBuilder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitSapInvoicePaymentRequest extends FormRequest
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
        if ($this->boolean('close_dds_only')) {
            return [
                'invoice_number' => 'required|string|max:100',
                'supplier_sap_code' => 'required|string|max:50',
                'amount' => 'required|numeric|min:0.01',
                'payment_date' => 'required|date|date_format:Y-m-d',
                'remarks' => 'nullable|string|max:1000',
                'payment_project' => 'nullable|string|max:50',
                'sap_doc' => 'nullable|string|max:50',
                'close_invoice_in_dds' => 'required|boolean|accepted',
                'close_dds_only' => 'required|boolean|accepted',
            ];
        }

        return [
            'invoice_number' => 'required|string|max:100',
            'supplier_sap_code' => 'required|string|max:50',
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date|date_format:Y-m-d',
            'remarks' => 'nullable|string|max:1000',
            'payment_project' => 'nullable|string|max:50',
            'sap_doc' => 'nullable|string|max:50',
            'close_invoice_in_dds' => 'sometimes|boolean',
            'close_dds_only' => 'sometimes|boolean',
            'payment_means' => ['required', Rule::in([SapVendorPaymentBuilder::MEANS_CASH, SapVendorPaymentBuilder::MEANS_TRANSFER])],
            'account_id' => 'required|integer|exists:accounts,id',
        ];
    }
}
