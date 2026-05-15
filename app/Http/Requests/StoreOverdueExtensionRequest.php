<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class StoreOverdueExtensionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, string|\Illuminate\Contracts\Validation\ValidationRule>>
     */
    public function rules(): array
    {
        return [
            'document_type' => ['required', 'in:payreq,realization'],
            'document_id' => ['required', 'integer'],
            'requested_due_date' => [
                'required',
                'date',
                'after:today',
                'before_or_equal:'.Carbon::today()->addDays(7)->toDateString(),
            ],
            'reason' => ['required', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'requested_due_date.after' => 'The requested due date must be after today.',
            'requested_due_date.before_or_equal' => 'The requested due date cannot be more than 7 days from today.',
        ];
    }
}
