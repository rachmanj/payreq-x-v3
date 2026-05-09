<?php

namespace App\Http\Requests\Help;

use Illuminate\Foundation\Http\FormRequest;

class HelpAskRequest extends FormRequest
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
            'message' => ['required', 'string', 'max:4000'],
            'locale' => ['nullable', 'in:auto,id,en'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'message.required' => 'Please enter a question.',
        ];
    }
}
