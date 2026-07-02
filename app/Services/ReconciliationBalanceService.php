<?php

namespace App\Services;

use App\Models\BankReconciliation;
use App\Models\BankStatementLine;
use App\Models\SapGlLine;

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
}
