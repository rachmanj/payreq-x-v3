<?php

namespace Tests\Unit;

use App\Support\AnggaranFormDetails;
use PHPUnit\Framework\TestCase;

class AnggaranFormDetailsTest extends TestCase
{
    public function test_line_amount_is_qty_times_unit_price_when_both_are_present(): void
    {
        $amount = AnggaranFormDetails::lineAmountFromRow([
            'qty' => 1,
            'unit_price' => 12000,
            'amount' => 1,
        ]);

        $this->assertSame(12000.0, $amount);
    }

    public function test_line_amount_uses_manual_amount_when_unit_price_is_zero(): void
    {
        $amount = AnggaranFormDetails::lineAmountFromRow([
            'qty' => 1,
            'unit_price' => 0,
            'amount' => 500,
        ]);

        $this->assertSame(500.0, $amount);
    }

    public function test_sum_line_amounts_totals_all_rows(): void
    {
        $sum = AnggaranFormDetails::sumLineAmounts([
            ['qty' => 2, 'unit_price' => 1000, 'amount' => 1],
            ['qty' => 1, 'unit_price' => 500, 'amount' => 0],
        ]);

        $this->assertSame(2500.0, $sum);
    }
}
