<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreReimbursePayreqRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // API key authentication is handled by middleware
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
            'employee_id' => 'required|exists:users,id',
            'remarks' => 'required|string|max:1000',
            'rab_id' => 'nullable|exists:anggarans,id',
            'details' => 'required|array|min:1',
            'details.*.description' => 'required|string|max:500',
            'details.*.amount' => 'required|numeric|min:0',
            'details.*.unit_no' => 'nullable|string|max:50',
            'details.*.nopol' => 'nullable|string|max:50',
            'details.*.type' => 'nullable|string|max:50',
            'details.*.qty' => 'nullable|numeric|min:0',
            'details.*.uom' => 'nullable|string|max:20',
            'details.*.km_position' => 'nullable|numeric|min:0',
            'submit' => 'boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'employee_id.required' => 'Employee ID is required',
            'employee_id.exists' => 'Employee not found in the system',
            'remarks.required' => 'Purpose/remarks is required',
            'remarks.max' => 'Remarks cannot exceed 1000 characters',
            'rab_id.exists' => 'RAB/Budget not found in the system',
            'details.required' => 'At least one detail item is required for reimburse payment request',
            'details.array' => 'Details must be an array',
            'details.min' => 'At least one detail item is required',
            'details.*.description.required' => 'Description is required for each detail item',
            'details.*.description.max' => 'Description cannot exceed 500 characters',
            'details.*.amount.required' => 'Amount is required for each detail item',
            'details.*.amount.numeric' => 'Amount must be a valid number',
            'details.*.amount.min' => 'Amount must be greater than or equal to 0',
            'details.*.qty.numeric' => 'Quantity must be a valid number',
            'details.*.qty.min' => 'Quantity must be greater than or equal to 0',
            'details.*.km_position.numeric' => 'KM position must be a valid number',
            'details.*.km_position.min' => 'KM position must be greater than or equal to 0',
            'submit.boolean' => 'Submit flag must be true or false',
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}
