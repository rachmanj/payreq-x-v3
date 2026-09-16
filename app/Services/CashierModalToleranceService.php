<?php

namespace App\Services;

use App\Models\Parameter;

final class CashierModalToleranceService
{
    private const DEFAULT_TOLERANCE = 1000;

    private ?int $tolerance = null;

    public function tolerance(): int
    {
        if ($this->tolerance !== null) {
            return $this->tolerance;
        }

        $parameter = Parameter::query()
            ->where('name1', 'cashier_modal_tolerance')
            ->where('name2', 'ALL')
            ->first();

        if ($parameter === null || $parameter->param_value === null || $parameter->param_value === '') {
            $this->tolerance = self::DEFAULT_TOLERANCE;

            return $this->tolerance;
        }

        $value = (int) $parameter->param_value;
        $this->tolerance = $value > 0 ? $value : self::DEFAULT_TOLERANCE;

        return $this->tolerance;
    }
}
