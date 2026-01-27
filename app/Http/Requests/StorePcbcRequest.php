<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePcbcRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'pcbc_date' => 'required|date',
            'project' => 'required|string',
            'kertas_100rb' => 'nullable|integer|min:0',
            'kertas_50rb' => 'nullable|integer|min:0',
            'kertas_20rb' => 'nullable|integer|min:0',
            'kertas_10rb' => 'nullable|integer|min:0',
            'kertas_5rb' => 'nullable|integer|min:0',
            'kertas_2rb' => 'nullable|integer|min:0',
            'kertas_1rb' => 'nullable|integer|min:0',
            'kertas_500' => 'nullable|integer|min:0',
            'kertas_100' => 'nullable|integer|min:0',
            'logam_1rb' => 'nullable|integer|min:0',
            'logam_500' => 'nullable|integer|min:0',
            'logam_200' => 'nullable|integer|min:0',
            'logam_100' => 'nullable|integer|min:0',
            'logam_50' => 'nullable|integer|min:0',
            'logam_25' => 'nullable|integer|min:0',
            'system_amount' => 'nullable|string',
            'sap_amount' => 'nullable|string',
            'pemeriksa1' => 'required|string',
            'pemeriksa2' => 'nullable|string',
            'approved_by' => 'required|string',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->has('fisik_amount')) {
                $calculatedAmount = app(\App\Services\PcbcService::class)->calculateFisikAmount($this);
                $submittedAmount = floatval(str_replace(',', '.', str_replace('.', '', $this->fisik_amount ?? '0')));
                
                if (!app(\App\Services\PcbcService::class)->validatePhysicalAmount($calculatedAmount, $submittedAmount)) {
                    $validator->errors()->add('fisik_amount', 'Physical amount does not match calculated amount from denominations.');
                }
            }
        });
    }
}
