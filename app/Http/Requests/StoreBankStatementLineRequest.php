<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBankStatementLineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'transaction_date' => ['nullable', 'date'],
            'value_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string', 'max:65535'],
            'reference' => ['nullable', 'string', 'max:191'],
            'debit' => ['required', 'numeric', 'min:0'],
            'credit' => ['required', 'numeric', 'min:0'],
            'balance' => ['nullable', 'numeric'],
            'line_notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
