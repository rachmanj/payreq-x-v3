<?php

namespace App\Support;

use App\Models\Anggaran;
use App\Models\AnggaranDetail;

class AnggaranFormDetails
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function rowsForForm(?Anggaran $anggaran = null): array
    {
        $old = old('details');
        if ($old !== null) {
            $rows = array_values($old);

            return $rows !== [] ? $rows : [[]];
        }

        if ($anggaran !== null && $anggaran->relationLoaded('details') && $anggaran->details->isNotEmpty()) {
            return $anggaran->details
                ->map(fn (AnggaranDetail $detail) => [
                    'description' => $detail->description,
                    'qty' => $detail->qty,
                    'unit' => $detail->unit,
                    'unit_price' => $detail->unit_price,
                    'amount' => $detail->amount,
                ])
                ->values()
                ->all();
        }

        return [[]];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    public static function lineAmountFromRow(array $row): float
    {
        $qty = isset($row['qty']) && $row['qty'] !== '' ? (float) $row['qty'] : 1.0;
        $unitPrice = isset($row['unit_price']) && $row['unit_price'] !== '' ? (float) $row['unit_price'] : 0.0;
        $amount = isset($row['amount']) && $row['amount'] !== '' ? (float) $row['amount'] : 0.0;

        if ($amount <= 0 && $qty > 0 && $unitPrice > 0) {
            $amount = round($qty * $unitPrice, 2);
        }

        return max(0, $amount);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    public static function sumLineAmounts(array $rows): float
    {
        $sum = 0.0;
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $sum += self::lineAmountFromRow($row);
        }

        return round($sum, 2);
    }
}
