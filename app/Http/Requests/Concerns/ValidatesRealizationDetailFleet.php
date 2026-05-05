<?php

namespace App\Http\Requests\Concerns;

use App\Models\Equipment;
use App\Models\Realization;
use Carbon\Carbon;
use Closure;
use Illuminate\Validation\Validator;

trait ValidatesRealizationDetailFleet
{
    protected static function canonicalDateOnlyString(mixed $value): string
    {
        $s = trim((string) $value);
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $s, $m)) {
            return sprintf('%04d-%02d-%02d', (int) $m[1], (int) $m[2], (int) $m[3]);
        }

        return Carbon::parse($value)->timezone(config('app.timezone'))->format('Y-m-d');
    }

    protected static function parseExpenseDateStartOfDay(string $raw): ?Carbon
    {
        $s = trim($raw);
        if ($s === '') {
            return null;
        }

        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $s, $m)) {
            try {
                return Carbon::createFromFormat(
                    'Y-m-d',
                    sprintf('%04d-%02d-%02d', (int) $m[1], (int) $m[2], (int) $m[3]),
                    config('app.timezone')
                )->startOfDay();
            } catch (\Throwable) {
                return null;
            }
        }

        try {
            return Carbon::parse($s)->timezone(config('app.timezone'))->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    protected function fleetInputFieldNames(): array
    {
        return ['unit_no', 'nopol', 'qty', 'uom', 'km_position'];
    }

    protected function normalizeType(?string $type): ?string
    {
        if ($type === null || $type === '') {
            return null;
        }

        return $type;
    }

    protected function shouldSkipFleetRules(): bool
    {
        if ($this->boolean('is_lotc')) {
            return true;
        }

        return str_starts_with((string) $this->input('description', ''), 'LOT Claim -');
    }

    protected function equipmentBaseQuery(Realization $realization)
    {
        $project = $realization->project;

        if (in_array($project, ['000H', 'APS', '001H'], true)) {
            return Equipment::query();
        }

        return Equipment::where('project', $project);
    }

    protected function applyRealizationFleetValidation(Validator $validator, Realization $realization, ?Closure $afterFuelBlock = null): void
    {
        $validator->after(function ($validator) use ($realization, $afterFuelBlock) {
            if ($this->shouldSkipFleetRules()) {
                return;
            }

            $type = $this->normalizeType($this->input('type'));
            $hasFleetInput = collect($this->fleetInputFieldNames())->contains(fn ($f) => $this->filled($f));

            if ($type === null && $hasFleetInput) {
                $validator->errors()->add('type', 'Select expense type before entering fleet/equipment fields.');
            }

            if ($type === 'fuel') {
                if (! $this->filled('unit_no')) {
                    $validator->errors()->add('unit_no', 'Unit is required for fuel.');
                }
                if (! $this->filled('qty')) {
                    $validator->errors()->add('qty', 'Quantity is required for fuel.');
                }
                if ($this->input('uom') !== 'liter') {
                    $validator->errors()->add('uom', 'Fuel lines must use UOM liter.');
                }
                if ($this->input('km_position') === null || $this->input('km_position') === '') {
                    $validator->errors()->add('km_position', 'HM reading is required for fuel.');
                }

                if ($this->filled('qty')) {
                    $qty = (float) $this->input('qty');
                    if ($qty <= 0) {
                        $validator->errors()->add('qty', 'Quantity must be greater than zero for fuel.');
                    }
                    if ($qty > 500000) {
                        $validator->errors()->add('qty', 'Quantity is too high; check the value.');
                    }
                }

                if ($afterFuelBlock) {
                    $afterFuelBlock($validator, $realization);
                }

                if ($this->filled('unit_no')) {
                    $this->enforceUnitEquipmentAndNopol($validator, $realization);
                }

                return;
            }

            if (in_array($type, ['service', 'tax', 'other'], true)) {
                if ($this->filled('qty') && $this->filled('uom') && $this->input('uom') === 'liter' && ! $this->filled('unit_no')) {
                    $validator->errors()->add('unit_no', 'Select a unit when recording fuel volume (liter) for this type.');
                }
            }

            if ($this->filled('unit_no')) {
                $this->enforceUnitEquipmentAndNopol($validator, $realization);
            }
        });
    }

    protected function enforceUnitEquipmentAndNopol(Validator $validator, Realization $realization): void
    {
        $unitCode = $this->input('unit_no');
        $exists = $this->equipmentBaseQuery($realization)
            ->where('unit_code', $unitCode)
            ->exists();

        if (! $exists) {
            $validator->errors()->add('unit_no', 'The selected unit is not valid for this realization project.');
        } else {
            $this->validateNopolMatchesUnit($validator, $realization, (string) $unitCode);
        }
    }

    protected function validateNopolMatchesUnit(Validator $validator, Realization $realization, string $unitCode): void
    {
        if (! $this->filled('nopol')) {
            return;
        }

        $equipment = $this->equipmentBaseQuery($realization)
            ->where('unit_code', $unitCode)
            ->first();

        if (! $equipment || ! $equipment->nomor_polisi) {
            return;
        }

        $normalizedInput = strtoupper(preg_replace('/\s+/', '', (string) $this->input('nopol')));
        $normalizedMaster = strtoupper(preg_replace('/\s+/', '', (string) $equipment->nomor_polisi));

        if ($normalizedInput !== '' && $normalizedInput !== $normalizedMaster) {
            $validator->errors()->add('nopol', 'No polisi does not match the selected unit in the equipment list.');
        }
    }

    protected function applyExpenseDateBusinessRules(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $raw = $this->input('expense_date');
            if ($raw === null || $raw === '') {
                return;
            }

            $expenseDay = static::parseExpenseDateStartOfDay((string) $raw);
            if ($expenseDay === null) {
                return;
            }

            if ($expenseDay->gt(Carbon::now(config('app.timezone'))->startOfDay())) {
                $validator->errors()->add('expense_date', 'Expense date cannot be in the future.');
            }
        });
    }
}
