<?php

namespace App\Http\Requests;

use App\Models\Bilyet;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBilyetRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Bilyet::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'prefix' => 'required|string|max:10',
            'nomor' => 'required|string|max:30',
            'giro_id' => 'required|exists:giros,id',
            'type' => ['required', Rule::in(Bilyet::TYPES)],
            'amount' => 'nullable|numeric|min:0',
            'bilyet_date' => 'nullable|date',
            'cair_date' => 'nullable|date|after_or_equal:bilyet_date',
            'remarks' => 'nullable|string|max:500',
            'project' => 'required|string|max:10',
            'purpose' => ['nullable', Rule::in(Bilyet::PURPOSES)],
            'loan_id' => 'nullable|exists:loans,id',
            'file_upload' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'type.in' => 'Type must be one of: ' . implode(', ', array_map('ucfirst', Bilyet::TYPES)),
            'cair_date.after_or_equal' => 'Cair date must be on or after bilyet date',
            'giro_id.exists' => 'Selected bank account does not exist',
            'amount.min' => 'Amount must be a positive number',
            'file_upload.mimes' => 'File must be a PDF or image file',
            'file_upload.max' => 'File size must not exceed 2MB',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'giro_id' => 'bank account',
            'bilyet_date' => 'bilyet date',
            'cair_date' => 'settlement date',
            'file_upload' => 'file',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Check for duplicate bilyet number
            if ($this->prefix && $this->nomor) {
                $exists = Bilyet::where('prefix', $this->prefix)
                    ->where('nomor', $this->nomor)
                    ->exists();

                if ($exists) {
                    $validator->errors()->add('nomor', 'Bilyet number already exists');
                }
            }
        });
    }
}
