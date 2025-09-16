<?php

namespace App\Http\Requests;

use App\Models\Bilyet;
use Illuminate\Foundation\Http\FormRequest;

class SuperAdminUpdateBilyetRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only superadmin users can access this
        return $this->user()->hasRole('superadmin');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $bilyetId = $this->route('id');

        return [
            'prefix' => 'nullable|string|max:10',
            'nomor' => 'required|string|max:30',
            'type' => 'required|string|in:cek,bg,LOA,debit',
            'giro_id' => 'required|exists:giros,id',
            'bilyet_date' => 'nullable|date',
            'cair_date' => 'nullable|date|after_or_equal:bilyet_date',
            'receive_date' => 'nullable|date',
            'amount' => 'nullable|numeric|min:0',
            'remarks' => 'nullable|string|max:500',
            'loan_id' => 'nullable|exists:loans,id',
            'project' => 'nullable|string|max:50',
            'status' => 'required|string|in:onhand,release,cair,void',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'nomor.required' => 'Bilyet number is required',
            'type.required' => 'Bilyet type is required',
            'type.in' => 'Invalid bilyet type',
            'giro_id.required' => 'Bank account selection is required',
            'giro_id.exists' => 'Selected bank account does not exist',
            'cair_date.after_or_equal' => 'Settlement date must be on or after bilyet date',
            'amount.min' => 'Amount must be a positive number',
            'loan_id.exists' => 'Selected loan does not exist',
            'status.required' => 'Status is required',
            'status.in' => 'Invalid status',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'prefix' => 'prefix',
            'nomor' => 'bilyet number',
            'type' => 'bilyet type',
            'giro_id' => 'bank account',
            'bilyet_date' => 'bilyet date',
            'cair_date' => 'settlement date',
            'receive_date' => 'receive date',
            'amount' => 'amount',
            'remarks' => 'remarks',
            'loan_id' => 'loan',
            'project' => 'project',
            'status' => 'status',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $bilyetId = $this->route('id');
            $prefix = $this->input('prefix');
            $nomor = $this->input('nomor');

            // Check for duplicate bilyet number + prefix combination
            if ($prefix && $nomor) {
                $existingBilyet = Bilyet::where('prefix', $prefix)
                    ->where('nomor', $nomor)
                    ->where('id', '!=', $bilyetId)
                    ->first();

                if ($existingBilyet) {
                    $validator->warnings()->add(
                        'nomor',
                        "Warning: Bilyet number '{$prefix}{$nomor}' already exists. Update will proceed but this may cause confusion."
                    );
                }
            }
        });
    }
}
