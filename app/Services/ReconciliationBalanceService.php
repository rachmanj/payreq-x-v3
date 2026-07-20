<?php

namespace App\Services;

use App\Models\BankReconciliation;
use App\Models\BankStatementLine;
use App\Models\SapGlLine;
use Illuminate\Support\Collection;

class ReconciliationBalanceService
{
    public const TOLERANCE = 0.005;

    public function bankNet(BankReconciliation $reconciliation): float
    {
        return round((float) $reconciliation->bankStatementLines()
            ->where('matched_status', '!=', BankStatementLine::MATCH_EXCLUDED)
            ->selectRaw('COALESCE(SUM(debit - credit), 0) as net')
            ->value('net'), 2);
    }

    public function bookNet(BankReconciliation $reconciliation): float
    {
        return round((float) $reconciliation->sapGlLines()
            ->where('matched_status', '!=', SapGlLine::MATCH_EXCLUDED)
            ->selectRaw('COALESCE(SUM(debit - credit), 0) as net')
            ->value('net'), 2);
    }

    public function difference(BankReconciliation $reconciliation): float
    {
        return round($this->bankNet($reconciliation) + $this->bookNet($reconciliation), 2);
    }

    public function isBalanced(BankReconciliation $reconciliation): bool
    {
        return abs($this->difference($reconciliation)) < self::TOLERANCE;
    }

    /**
     * @return array{bank_net: float, book_net: float, difference: float, is_balanced: bool}
     */
    public function summary(BankReconciliation $reconciliation): array
    {
        $bankNet = $this->bankNet($reconciliation);
        $bookNet = $this->bookNet($reconciliation);
        $difference = round($bankNet + $bookNet, 2);

        return [
            'bank_net' => $bankNet,
            'book_net' => $bookNet,
            'difference' => $difference,
            'is_balanced' => abs($difference) < self::TOLERANCE,
        ];
    }

    /**
     * Formal bank reconciliation statement driven by closing balances and unmatched (reconciling) items.
     *
     * adjusted_bank = closing_balance_bank + SUM(unmatched book net)
     * adjusted_book = closing_balance_book - SUM(unmatched bank net)
     *
     * @return array{
     *     incomplete: bool,
     *     is_reconciled: bool,
     *     closing_balance_bank: float|null,
     *     closing_balance_book: float|null,
     *     opening_balance_bank: float|null,
     *     opening_balance_book: float|null,
     *     opening_discrepancy: float|null,
     *     unmatched_bank_net: float,
     *     unmatched_book_net: float,
     *     adjusted_bank: float|null,
     *     adjusted_book: float|null,
     *     unexplained_difference: float|null,
     *     bank_items: array<string, list<array{id: int, date: string|null, description: string|null, net: float, category: string}>>,
     *     book_items: array<string, list<array{id: int, date: string|null, description: string|null, net: float, category: string}>>,
     *     category_totals: array<string, float>,
     *     movement_difference: float,
     *     diagnostic: string|null
     * }
     */
    public function reconciliationStatement(BankReconciliation $reconciliation): array
    {
        $unmatchedBank = $reconciliation->bankStatementLines()
            ->where('matched_status', BankStatementLine::MATCH_UNMATCHED)
            ->orderBy('line_order')
            ->orderBy('id')
            ->get();

        $unmatchedBook = $reconciliation->sapGlLines()
            ->where('matched_status', SapGlLine::MATCH_UNMATCHED)
            ->orderBy('posting_date')
            ->orderBy('id')
            ->get();

        $unmatchedBankNet = round($unmatchedBank->sum(fn (BankStatementLine $line) => $line->net()), 2);
        $unmatchedBookNet = round($unmatchedBook->sum(fn (SapGlLine $line) => $line->net()), 2);

        $bankItems = $this->groupBankItems($unmatchedBank);
        $bookItems = $this->groupBookItems($unmatchedBook);
        $categoryTotals = $this->categoryTotals($bankItems, $bookItems);

        $openingBank = $reconciliation->opening_balance_bank !== null
            ? round((float) $reconciliation->opening_balance_bank, 2)
            : null;
        $openingBook = $reconciliation->opening_balance_book !== null
            ? round((float) $reconciliation->opening_balance_book, 2)
            : null;
        $closingBank = $reconciliation->closing_balance_bank !== null
            ? round((float) $reconciliation->closing_balance_bank, 2)
            : null;
        $closingBook = $reconciliation->closing_balance_book !== null
            ? round((float) $reconciliation->closing_balance_book, 2)
            : null;

        $openingDiscrepancy = ($openingBank !== null && $openingBook !== null)
            ? round($openingBank - $openingBook, 2)
            : null;

        $movementDifference = $this->difference($reconciliation);

        $incomplete = $closingBank === null || $closingBook === null;

        if ($incomplete) {
            return [
                'incomplete' => true,
                'is_reconciled' => false,
                'closing_balance_bank' => $closingBank,
                'closing_balance_book' => $closingBook,
                'opening_balance_bank' => $openingBank,
                'opening_balance_book' => $openingBook,
                'opening_discrepancy' => $openingDiscrepancy,
                'unmatched_bank_net' => $unmatchedBankNet,
                'unmatched_book_net' => $unmatchedBookNet,
                'adjusted_bank' => null,
                'adjusted_book' => null,
                'unexplained_difference' => null,
                'bank_items' => $bankItems,
                'book_items' => $bookItems,
                'category_totals' => $categoryTotals,
                'movement_difference' => $movementDifference,
                'diagnostic' => 'Closing balances are required on both bank and book sides before the reconciliation can be submitted.',
            ];
        }

        $adjustedBank = round($closingBank + $unmatchedBookNet, 2);
        $adjustedBook = round($closingBook - $unmatchedBankNet, 2);
        $unexplainedDifference = round($adjustedBank - $adjustedBook, 2);
        $isReconciled = abs($unexplainedDifference) < self::TOLERANCE;

        $diagnostic = null;
        if (! $isReconciled) {
            $parts = [
                'Unexplained difference: '.number_format($unexplainedDifference, 2)
                    .' (adjusted bank '.number_format($adjustedBank, 2)
                    .' vs adjusted book '.number_format($adjustedBook, 2).').',
            ];

            if ($openingDiscrepancy !== null && abs($openingDiscrepancy) >= self::TOLERANCE) {
                $parts[] = 'Opening balances also differ by '.number_format($openingDiscrepancy, 2).'.';
            }

            $diagnostic = implode(' ', $parts);
        }

        return [
            'incomplete' => false,
            'is_reconciled' => $isReconciled,
            'closing_balance_bank' => $closingBank,
            'closing_balance_book' => $closingBook,
            'opening_balance_bank' => $openingBank,
            'opening_balance_book' => $openingBook,
            'opening_discrepancy' => $openingDiscrepancy,
            'unmatched_bank_net' => $unmatchedBankNet,
            'unmatched_book_net' => $unmatchedBookNet,
            'adjusted_bank' => $adjustedBank,
            'adjusted_book' => $adjustedBook,
            'unexplained_difference' => $unexplainedDifference,
            'bank_items' => $bankItems,
            'book_items' => $bookItems,
            'category_totals' => $categoryTotals,
            'movement_difference' => $movementDifference,
            'diagnostic' => $diagnostic,
        ];
    }

    /**
     * @param  Collection<int, BankStatementLine>  $lines
     * @return array<string, list<array{id: int, date: string|null, description: string|null, net: float, category: string}>>
     */
    protected function groupBankItems(Collection $lines): array
    {
        $grouped = [];

        foreach ($lines as $line) {
            $category = $line->reconcilingCategory();
            $grouped[$category][] = [
                'id' => $line->id,
                'date' => $line->transaction_date?->format('Y-m-d'),
                'description' => $line->description,
                'net' => $line->net(),
                'category' => $category,
            ];
        }

        return $grouped;
    }

    /**
     * @param  Collection<int, SapGlLine>  $lines
     * @return array<string, list<array{id: int, date: string|null, description: string|null, net: float, category: string}>>
     */
    protected function groupBookItems(Collection $lines): array
    {
        $grouped = [];

        foreach ($lines as $line) {
            $category = $line->reconcilingCategory();
            $grouped[$category][] = [
                'id' => $line->id,
                'date' => $line->posting_date?->format('Y-m-d'),
                'description' => $line->description,
                'net' => $line->net(),
                'category' => $category,
            ];
        }

        return $grouped;
    }

    /**
     * @param  array<string, list<array{net: float}>>  $bankItems
     * @param  array<string, list<array{net: float}>>  $bookItems
     * @return array<string, float>
     */
    protected function categoryTotals(array $bankItems, array $bookItems): array
    {
        $totals = [];

        foreach (array_merge($bankItems, $bookItems) as $category => $items) {
            $totals[$category] = round(array_sum(array_column($items, 'net')), 2);
        }

        return $totals;
    }
}
