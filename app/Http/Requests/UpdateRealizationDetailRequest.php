<?php

namespace App\Http\Requests;

use App\Http\Controllers\UserController;
use App\Http\Requests\Concerns\ValidatesRealizationDetailFleet;
use App\Models\Realization;
use App\Models\RealizationDetail;
use App\Support\RealizationDetailOdometerMonotonicityValidator;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateRealizationDetailRequest extends FormRequest
{
    use ValidatesRealizationDetailFleet;

    private ?RealizationDetail $resolvedDetail = null;

    public function authorize(): bool
    {
        $roles = app(UserController::class)->getUserRoles();
        if (in_array('superadmin', $roles, true)) {
            return true;
        }

        $detail = $this->resolveDetail();
        if (! $detail) {
            return Auth::check();
        }

        $realization = $detail->realization;
        if (! $realization) {
            return false;
        }

        return (int) $realization->user_id === (int) Auth::id();
    }

    protected function resolveDetail(): ?RealizationDetail
    {
        if ($this->resolvedDetail !== null) {
            return $this->resolvedDetail;
        }

        $param = $this->route('detail');
        if ($param instanceof RealizationDetail) {
            $this->resolvedDetail = $param;

            return $this->resolvedDetail;
        }

        if ($param !== null && $param !== '') {
            $this->resolvedDetail = RealizationDetail::find($param);

            return $this->resolvedDetail;
        }

        $bodyId = $this->input('realization_detail_id');
        if ($bodyId !== null && $bodyId !== '') {
            $this->resolvedDetail = RealizationDetail::find($bodyId);
        }

        return $this->resolvedDetail;
    }

    protected function realizeForValidation(): ?Realization
    {
        $detail = $this->resolveDetail();
        if (! $detail) {
            return null;
        }

        return Realization::with('payreq')->find($detail->realization_id);
    }

    protected function prepareForValidation(): void
    {
        $detailParam = $this->route('detail');
        if ($detailParam instanceof RealizationDetail) {
            $this->merge(['realization_detail_id' => $detailParam->id]);
        }

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
        return [
            'realization_detail_id' => [
                Rule::requiredIf(fn () => $this->route()->parameter('detail') === null),
                'nullable',
                'exists:realization_details,id',
            ],
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
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $realization = $this->realizeForValidation();
        if ($realization) {
            $this->applyRealizationFleetValidation($validator, $realization);
            $this->applyExpenseDateBusinessRules($validator, $realization);
        }

        $excludeId = $this->resolveDetail()?->id;

        $validator->after(function ($validator) use ($excludeId) {
            RealizationDetailOdometerMonotonicityValidator::validate(
                $validator,
                $this->all(),
                $excludeId
            );
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function realizationDetailPayload(): array
    {
        $validated = $this->validated();

        $payload = collect($validated)->only([
            'description',
            'amount',
            'unit_no',
            'nopol',
            'type',
            'qty',
            'uom',
            'km_position',
            'expense_date',
        ]);

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

            if ($key === 'expense_date' && $value !== null && $value !== '') {
                return Carbon::parse($value)->format('Y-m-d');
            }

            return $value;
        })->all();
    }
}
