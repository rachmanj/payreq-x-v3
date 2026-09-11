<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Validation\Validator;

final class ActivityPeriodOptions
{
    public static function currentMonthValue(): string
    {
        return now()->format('Y-m');
    }

    /**
     * @return array<string, string>
     */
    public static function monthly(): array
    {
        $now = now();
        $currentValue = $now->format('Y-m');
        $startYear = $now->year - 1;
        $endYear = $now->year + 1;

        $allMonths = [];
        for ($year = $startYear; $year <= $endYear; $year++) {
            for ($month = 1; $month <= 12; $month++) {
                $date = Carbon::create($year, $month, 1);
                $value = $date->format('Y-m');
                $allMonths[$value] = $date->locale('id')->translatedFormat('M Y');
            }
        }

        $ordered = [];
        if (isset($allMonths[$currentValue])) {
            $ordered[$currentValue] = $allMonths[$currentValue];
            unset($allMonths[$currentValue]);
        }

        krsort($allMonths, SORT_STRING);

        return array_merge($ordered, $allMonths);
    }

    /**
     * @return array<string, string>
     */
    public static function annual(): array
    {
        $currentYear = now()->year;
        $options = [];

        foreach ([$currentYear - 1, $currentYear, $currentYear + 1] as $year) {
            $options[(string) $year] = "Tahunan {$year}";
        }

        return $options;
    }

    /**
     * @return array<int, string>
     */
    public static function validationRules(): array
    {
        return ['required', 'string', 'regex:/^\d{4}(-\d{2})?$/'];
    }

    /**
     * @return array<string, string>
     */
    public static function validationMessages(): array
    {
        return [
            'periode.regex' => 'Periode harus dalam format YYYY-MM atau YYYY.',
        ];
    }

    public static function validateMonthRange(Validator $validator, mixed $periode): void
    {
        if (! is_string($periode) || ! preg_match('/^\d{4}-(\d{2})$/', $periode, $matches)) {
            return;
        }

        $month = (int) $matches[1];
        if ($month < 1 || $month > 12) {
            $validator->errors()->add('periode', 'Periode harus dalam format YYYY-MM atau YYYY.');
        }
    }
}
