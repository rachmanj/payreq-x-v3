<?php

namespace App\Http\Requests;

use App\Models\BankReconciliation;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBankReconciliationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $periode = $this->input('periode');
        if (is_string($periode) && preg_match('/^\d{4}-\d{2}$/', $periode)) {
            $this->merge(['periode' => $periode.'-01']);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'giro_id' => ['required', 'integer', 'exists:giros,id'],
            'source_mode' => ['required', 'string', Rule::in([
                BankReconciliation::SOURCE_AI,
                BankReconciliation::SOURCE_MANUAL,
            ])],
            'dokumen_id' => [
                Rule::requiredIf(fn () => $this->input('source_mode') === BankReconciliation::SOURCE_AI),
                'nullable',
                'integer',
                Rule::exists('dokumens', 'id')->where(function ($query): void {
                    $query->where('type', 'koran')
                        ->where('giro_id', $this->input('giro_id'));
                }),
            ],
            'periode' => ['required', 'date'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $periode = Carbon::parse((string) $this->input('periode'))->startOfMonth();

            if ($this->input('source_mode') === BankReconciliation::SOURCE_AI) {
                $dokumen = \App\Models\Dokumen::query()->find((int) $this->input('dokumen_id'));
                if ($dokumen === null) {
                    return;
                }

                if ($dokumen->periode === null) {
                    $validator->errors()->add('dokumen_id', 'Selected dokumen has no period.');
                } else {
                    $docMonth = Carbon::parse($dokumen->periode)->format('Y-m');
                    if ($docMonth !== $periode->format('Y-m')) {
                        $validator->errors()->add('periode', 'Periode must match the selected dokumen month.');
                    }
                }
            }

            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $existingReconciliation = BankReconciliation::query()
                ->where('giro_id', (int) $this->input('giro_id'))
                ->where('periode', $periode->toDateString())
                ->first();

            if ($existingReconciliation !== null) {
                session()->flash('existing_bank_reconciliation_id', $existingReconciliation->id);
                $validator->errors()->add('periode', 'A bank reconciliation already exists for this giro and period.');
            }
        });
    }
}
