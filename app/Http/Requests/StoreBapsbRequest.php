<?php

namespace App\Http\Requests;

use App\Services\BapsbService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBapsbRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('akses_bapsb');
    }

    public function rules(): array
    {
        return [
            'period' => ['required', 'date_format:Y-m'],
            'project' => ['required', 'string', 'max:20'],
            'bapsb_date' => ['required', 'date'],
            'checker1' => ['required', 'string', 'max:120'],
            'checker2' => ['required', 'string', 'max:120'],
            'approved_by' => ['nullable', 'string', 'max:120'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.bilyet_id' => ['required', 'integer', 'exists:bilyets,id'],
            'lines.*.physical_present' => ['required', 'boolean'],
            'lines.*.location' => ['required', 'string', Rule::in(BapsbService::LOCATIONS)],
            'lines.*.location_note' => ['nullable', 'string', 'max:500'],
            'lines.*.remarks' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
