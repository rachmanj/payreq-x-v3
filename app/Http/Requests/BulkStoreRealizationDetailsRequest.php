<?php

namespace App\Http\Requests;

use App\Http\Controllers\UserController;
use App\Http\Requests\Concerns\ValidatesRealizationDetailFleet;
use App\Models\Payreq;
use App\Models\Realization;
use App\Support\RealizationDetailOdometerMonotonicityValidator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class BulkStoreRealizationDetailsRequest extends FormRequest
{
    use ValidatesRealizationDetailFleet;

    public function authorize(): bool
    {
        $id = $this->input('realization_id');
        if (! $id) {
            return true;
        }

        $realization = Realization::find($id);
        if (! $realization) {
            return true;
        }

        $roles = app(UserController::class)->getUserRoles();
        if (in_array('superadmin', $roles, true)) {
            return true;
        }

        return (int) $realization->user_id === (int) Auth::id();
    }

    protected function prepareForValidation(): void
    {
        $details = $this->input('details', []);
        if (! is_array($details)) {
            return;
        }

        foreach ($details as $index => $detail) {
            if (! is_array($detail)) {
                continue;
            }
            if (isset($detail['amount']) && is_string($detail['amount'])) {
                $details[$index]['amount'] = str_replace(',', '', $detail['amount']);
            }
            if (($detail['type'] ?? '') === '') {
                $details[$index]['type'] = null;
            }
            if (($detail['expense_date'] ?? '') === '') {
                $details[$index]['expense_date'] = null;
            }
        }

        $this->merge(['details' => $details]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $payreq = $this->realizationPayreq();

        $rabRules = ['nullable'];
        if ($payreq !== null && $payreq->isAdvanceMultiBudget()) {
            $rabRules = ['required', 'integer', 'exists:anggarans,id'];
        }

        return [
            'realization_id' => ['required', 'exists:realizations,id'],
            'details' => ['required', 'array', 'min:1'],
            'details.*.description' => ['required', 'string', 'max:255'],
            'details.*.amount' => ['required', 'numeric', 'min:0'],
            'details.*.type' => ['nullable', Rule::in(['fuel', 'service', 'tax', 'other'])],
            'details.*.unit_no' => ['nullable', 'string', 'max:20'],
            'details.*.nopol' => ['nullable', 'string', 'max:50'],
            'details.*.qty' => ['nullable', 'numeric'],
            'details.*.uom' => ['nullable', Rule::in(['liter', 'each'])],
            'details.*.km_position' => ['nullable', 'integer', 'min:0', 'max:99999999'],
            'details.*.expense_date' => ['nullable', 'date', 'before_or_equal:today'],
            'details.*.rab_id' => $rabRules,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'details.*.km_position' => 'HM reading',
            'details.*.unit_no' => 'unit',
            'details.*.qty' => 'quantity',
            'details.*.uom' => 'UOM',
            'details.*.expense_date' => 'expense date',
            'details.*.rab_id' => 'anggaran',
        ];
    }

    private function realizationPayreq(): ?Payreq
    {
        $realizationId = $this->input('realization_id');
        if (! $realizationId) {
            return null;
        }

        return Realization::with([
            'payreq' => fn ($q) => $q->with('anggaranAllocations'),
        ])->find($realizationId)?->payreq;
    }

    public function withValidator(Validator $validator): void
    {
        $realization = Realization::with(['payreq.anggaranAllocations'])->find($this->input('realization_id'));
        if (! $realization) {
            return;
        }

        $payreq = $realization->payreq;
        if ($payreq && $payreq->isAdvanceMultiBudget()) {
            $allowedIds = $payreq->allocatedAnggaranIds();
            $details = $this->input('details', []);
            foreach ($details as $index => $detail) {
                if (! is_array($detail)) {
                    continue;
                }
                if (count($allowedIds) === 0) {
                    $validator->errors()->add(
                        "details.{$index}.rab_id",
                        'This advance has no allocated anggaran rows; refresh the payreq or contact support.'
                    );
                } elseif (! empty($detail['rab_id']) && ! in_array((int) $detail['rab_id'], $allowedIds, true)) {
                    $validator->errors()->add(
                        "details.{$index}.rab_id",
                        'Selected anggaran must be one of the advance allocations.'
                    );
                }
            }
        }

        $validator->after(function ($validator) {
            $details = $this->input('details', []);
            if (! is_array($details)) {
                return;
            }

            foreach ($details as $index => $detail) {
                if (! is_array($detail)) {
                    continue;
                }

                RealizationDetailOdometerMonotonicityValidator::validate(
                    $validator,
                    $detail,
                    null
                );
            }
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function normalizedDetailPayloads(): array
    {
        $payreq = $this->realizationPayreq();
        $multiBudget = $payreq !== null && $payreq->isAdvanceMultiBudget();

        $columns = [
            'description',
            'amount',
            'unit_no',
            'nopol',
            'type',
            'qty',
            'uom',
            'km_position',
            'expense_date',
        ];

        if ($multiBudget) {
            $columns[] = 'rab_id';
        }

        $payloads = [];
        foreach ($this->validated('details') as $detail) {
            $row = collect($detail)->only($columns);

            $payloads[] = $row->map(function ($value, string $key) {
                if (in_array($key, ['type', 'unit_no', 'nopol', 'uom'], true)) {
                    return $value === '' || $value === null ? null : $value;
                }

                if ($key === 'qty' && $value !== null && $value !== '') {
                    return (int) round((float) $value);
                }

                if ($key === 'km_position' && $value !== null && $value !== '') {
                    return (int) $value;
                }

                if ($key === 'expense_date' && $value !== null && $value !== '') {
                    return static::canonicalDateOnlyString($value);
                }

                if ($key === 'rab_id' && $value !== null && $value !== '') {
                    return (int) $value;
                }

                return $value;
            })->all();
        }

        return $payloads;
    }
}
