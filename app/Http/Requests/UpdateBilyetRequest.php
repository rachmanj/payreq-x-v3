<?php

namespace App\Http\Requests;

use App\Models\Bilyet;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class UpdateBilyetRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $bilyet = $this->route('bilyet') ?? Bilyet::find($this->route('id'));
        return $this->user()->can('update', $bilyet);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $bilyet = $this->route('bilyet') ?? Bilyet::find($this->route('id'));

        $rules = [
            'bilyet_date' => 'nullable|date',
            'cair_date' => 'nullable|date|after_or_equal:bilyet_date',
            'amount' => 'nullable|numeric|min:0',
            'remarks' => 'nullable|string|max:500',
            'is_void' => 'nullable|boolean',
        ];

        if (!$this->boolean('is_void')) {
            $rules['amount'] = 'required|numeric|min:0';
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'cair_date.after_or_equal' => 'Cair date must be on or after bilyet date',
            'amount.required' => 'Amount is required when releasing or settling a bilyet',
            'amount.min' => 'Amount must be a positive number',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $bilyet = $this->route('bilyet') ?? Bilyet::find($this->route('id'));

        if ($bilyet) {
            session()->flash('bilyet_failed_id', $bilyet->id);
            session()->flash('bilyet_failed_status', $bilyet->status);
        }

        throw (new ValidationException($validator))
            ->redirectTo($this->getRedirectUrl())
            ->errorBag($this->errorBag);
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'bilyet_date' => 'bilyet date',
            'cair_date' => 'settlement date',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $bilyet = $this->route('bilyet') ?? Bilyet::find($this->route('id'));

            if ($bilyet) {
                // Check if trying to void a settled bilyet
                if ($this->is_void && !$bilyet->canBeVoided()) {
                    $validator->errors()->add('is_void', 'Cannot void bilyet after it has been settled');
                }

                // Check if trying to modify a settled bilyet
                if (!$this->is_void && $bilyet->status === 'cair') {
                    $validator->errors()->add('status', 'Cannot modify bilyet after it has been settled');
                }
            }
        });
    }
}
