<?php

namespace App\Http\Requests\Help;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HelpFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->can('akses_help');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['bug', 'feature'])],
            'title' => ['required', 'string', 'max:512'],
            'body' => ['required', 'string', 'max:20000'],
            'steps_to_reproduce' => ['nullable', 'string', 'max:20000'],
        ];
    }
}
