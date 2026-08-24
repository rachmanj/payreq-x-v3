<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitUtilityApInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('submit_sap_ap_invoice_utilities') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'bill_ids' => 'required|array|min:1',
            'bill_ids.*' => 'integer|exists:utility_bills,id',
            'num_at_card' => 'required|string|max:100',
        ];
    }
}
