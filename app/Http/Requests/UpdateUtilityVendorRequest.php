<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUtilityVendorRequest extends FormRequest
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
            'vendors' => 'required|array',
            'vendors.pln' => 'nullable|exists:sap_business_partners,id',
            'vendors.pdam' => 'nullable|exists:sap_business_partners,id',
            'vendors.telkom' => 'nullable|exists:sap_business_partners,id',
        ];
    }
}
