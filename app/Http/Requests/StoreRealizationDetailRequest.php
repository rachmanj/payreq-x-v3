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

class StoreRealizationDetailRequest extends FormRequest
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
        if ($this->has('amount') && is_string($this->amount)) {
            $this->merge([
                'amount' => str_replace(',', '', $this->amount),
            ]);
        }

        $type = $this->input('type');
        if ($type === '') {
            $this->merge(['type' => null]);
        }

        if ($this->input('expense_date') === '') {
            $this->merge(['expense_date' => null]);
        }
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
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'type' => ['nullable', Rule::in(['fuel', 'service', 'tax', 'other'])],
            'unit_no' => ['nullable', 'string', 'max:20'],
            'nopol' => ['nullable', 'string', 'max:50'],
            'qty' => ['nullable', 'numeric'],
            'uom' => ['nullable', Rule::in(['liter', 'each'])],
            'km_position' => ['nullable', 'integer', 'min:0', 'max:99999999'],
            'is_lotc' => ['nullable', 'boolean'],
            'expense_date' => [
                Rule::requiredIf(fn () => ! $this->shouldSkipFleetRules()),
                'nullable',
                'date',
                'before_or_equal:today',
            ],
            'rab_id' => $rabRules,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'km_position' => 'HM reading',
            'unit_no' => 'unit',
            'qty' => 'quantity',
            'uom' => 'UOM',
            'expense_date' => 'expense date',
            'rab_id' => 'anggaran',
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
        if ($realization) {
            $this->applyRealizationFleetValidation($validator, $realization);
            $this->applyExpenseDateBusinessRules($validator);

            $payreq = $realization->payreq;
            if ($payreq && $payreq->isAdvanceMultiBudget()) {
                $allowedIds = $payreq->allocatedAnggaranIds();
                if (count($allowedIds) === 0) {
                    $validator->errors()->add(
                        'rab_id',
                        'This advance has no allocated anggaran rows; refresh the payreq or contact support.'
                    );
                } elseif ($this->filled('rab_id') && ! in_array((int) $this->input('rab_id'), $allowedIds, true)) {
                    $validator->errors()->add(
                        'rab_id',
                        'Selected anggaran must be one of the advance allocations.'
                    );
                }
            }
        }

        $validator->after(function ($validator) {
            RealizationDetailOdometerMonotonicityValidator::validate(
                $validator,
                $this->all(),
                null
            );
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function realizationDetailPayload(): array
    {
        $validated = $this->validated();

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

        $payreq = $this->realizationPayreq();
        if ($payreq !== null && $payreq->isAdvanceMultiBudget()) {
            $columns[] = 'rab_id';
        }

        $payload = collect($validated)->only($columns);

        return $payload->map(function ($value, string $key) {
            if (in_array($key, ['type', 'unit_no', 'nopol', 'uom'], true)) {
                return $value === '' || $value === null ? null : $value;
            }

            if ($key === 'qty' && $value !== null && $value !== '') {
                return (int) round((float) $value);
            }

            if ($key === 'km_position' && $value !== null && $value !== '') {
                return (int) $value;
            }

            if ($key === 'rab_id' && $value !== null && $value !== '') {
                return (int) $value;
            }

            return $value;
        })->all();
    }
}
