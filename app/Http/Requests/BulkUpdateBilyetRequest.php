<?php

namespace App\Http\Requests;

use App\Models\Bilyet;
use Illuminate\Foundation\Http\FormRequest;

class BulkUpdateBilyetRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Bilyet::class); // Using create permission for bulk operations
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'bilyet_ids' => 'required|array|min:1',
            'bilyet_ids.*' => 'required|exists:bilyets,id',
            'bilyet_date' => 'nullable|date',
            'amount' => 'nullable|numeric|min:0',
            'remarks' => 'nullable|string|max:500',
            'from_page' => 'nullable|string',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'bilyet_ids.required' => 'Please select at least one bilyet to update',
            'bilyet_ids.min' => 'Please select at least one bilyet to update',
            'bilyet_ids.*.exists' => 'One or more selected bilyets do not exist',
            'amount.min' => 'Amount must be a positive number',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'bilyet_ids' => 'selected bilyets',
            'bilyet_date' => 'bilyet date',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->bilyet_ids) {
                // Check if all selected bilyets are onhand status
                $invalidBilyets = Bilyet::whereIn('id', $this->bilyet_ids)
                    ->where('status', '!=', 'onhand')
                    ->pluck('id')
                    ->toArray();

                if (!empty($invalidBilyets)) {
                    $validator->errors()->add(
                        'bilyet_ids',
                        'Only onhand bilyets can be updated in bulk. Invalid IDs: ' . implode(', ', $invalidBilyets)
                    );
                }

                // Check if user has access to all selected bilyets
                $userRoles = app(\App\Http\Controllers\UserController::class)->getUserRoles();
                if (!in_array('admin', $userRoles) && !in_array('superadmin', $userRoles)) {
                    $unauthorizedBilyets = Bilyet::whereIn('id', $this->bilyet_ids)
                        ->where('project', '!=', auth()->user()->project)
                        ->pluck('id')
                        ->toArray();

                    if (!empty($unauthorizedBilyets)) {
                        $validator->errors()->add(
                            'bilyet_ids',
                            'You do not have access to some selected bilyets. Unauthorized IDs: ' . implode(', ', $unauthorizedBilyets)
                        );
                    }
                }
            }
        });
    }
}
