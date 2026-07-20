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
            'meeting_ids' => ['sometimes', 'array'],
            'meeting_ids.*' => ['integer', 'exists:meetings,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'stream' => ['sometimes', 'boolean'],
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

    /**
     * @return array{
     *   meeting_ids?: array<int, int>,
     *   date_from?: string|null,
     *   date_to?: string|null,
     * }
     */
    public function filters(): array
    {
        $filters = [];

        if ($this->filled('meeting_ids')) {
            $filters['meeting_ids'] = array_map('intval', $this->validated('meeting_ids'));
        }

        if ($this->filled('date_from')) {
            $filters['date_from'] = $this->validated('date_from');
        }

        if ($this->filled('date_to')) {
            $filters['date_to'] = $this->validated('date_to');
        }

        return $filters;
    }
}
