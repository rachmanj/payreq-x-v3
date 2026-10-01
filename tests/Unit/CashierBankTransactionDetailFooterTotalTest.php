<?php

namespace Tests\Unit;

use App\Services\CashierBankTransactionJournalLinesService;
use Tests\TestCase;

class CashierBankTransactionDetailFooterTotalTest extends TestCase
{
    public function test_bank_interest_mixed_cashier_lines_footer_total_matches_journal_header(): void
    {
        $service = app(CashierBankTransactionJournalLinesService::class);

        $total = $service->bankInterestJournalTotalFromCashierLines(
            ['71201001', '71101001', '71201007'],
            [25000, 6374.31, 1274.86],
        );

        $this->assertEqualsWithDelta(26274.86, $total, 0.001);
    }

    public function test_bank_interest_single_income_line_footer_total_equals_line_amount(): void
    {
        $service = app(CashierBankTransactionJournalLinesService::class);

        $total = $service->bankInterestJournalTotalFromCashierLines(
            ['71101001'],
            [3926.95],
        );

        $this->assertEqualsWithDelta(3926.95, $total, 0.001);
    }

    public function test_non_interest_footer_total_is_sum_of_cashier_lines(): void
    {
        $service = app(CashierBankTransactionJournalLinesService::class);

        $total = $service->detailFooterTotalForTransactionType(
            'transfer_to_petty_cash',
            ['11101005'],
            [5_000_000],
        );

        $this->assertEqualsWithDelta(5_000_000, $total, 0.001);
    }
}
