<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApproveOverdueExtensionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('approve_overdue_extension') ?? false;
    }

    /**
     * @return array<string, array<int, string|\Illuminate\Contracts\Validation\ValidationRule>>
     */
    public function rules(): array
    {
        return [
            'requested_due_date' => ['required', 'date', 'after:today'],
        ];
    }
}
