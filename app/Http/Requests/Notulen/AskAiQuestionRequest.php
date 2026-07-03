<?php

namespace App\Http\Requests\Notulen;

use Illuminate\Foundation\Http\FormRequest;

class AskAiQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->can('akses_notulen');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'question' => ['required', 'string', 'max:4000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'question.required' => 'Silakan masukkan pertanyaan.',
        ];
    }
}
