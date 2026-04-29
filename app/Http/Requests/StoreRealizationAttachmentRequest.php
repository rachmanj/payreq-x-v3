<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRealizationAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create_realization_attachments');
    }

    /**
     * @return array<string, array<int, string|\Illuminate\Contracts\Validation\ValidationRule>>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:10240', 'mimes:jpeg,jpg,png,gif,webp,pdf'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Please choose a file to upload.',
            'file.mimes' => 'Only image or PDF files are allowed.',
            'file.max' => 'The file may not be larger than 10 MB.',
        ];
    }
}
