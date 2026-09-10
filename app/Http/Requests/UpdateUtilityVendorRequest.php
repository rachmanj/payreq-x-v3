<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\SapBusinessPartner;

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
        // Hanya BP bertipe Supplier/Vendor & aktif yang boleh di-mapping.
        // BP bertipe Customer (mis. perusahaan sendiri) bikin OP/AP gagal di SAP Service Layer.
        $supplierOnly = Rule::exists('sap_business_partners', 'id')
            ->where(function ($query) {
                $query->whereIn('type', [SapBusinessPartner::TYPE_SUPPLIER, 'S'])
                    ->where('active', 1);
            });

        return [
            'vendors' => 'required|array',
            'vendors.pln' => ['nullable', $supplierOnly],
            'vendors.pdam' => ['nullable', $supplierOnly],
            'vendors.telkom' => ['nullable', $supplierOnly],
        ];
    }
}
