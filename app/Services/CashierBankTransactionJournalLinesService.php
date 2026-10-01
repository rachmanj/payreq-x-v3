<?php

namespace App\Services;

class CashierBankTransactionJournalLinesService
{
    public const BANK_INTEREST_INCOME_PREFIX = '71101';

    public const BANK_INTEREST_EXPENSE_PREFIX = '71201';

    public const BANK_INTEREST_MISSING_INCOME_MESSAGE = 'Transaksi Bank Interest harus memuat minimal satu akun pendapatan bank (71101...).';

    public function __construct(
        protected CashierBankTransactionDirectSapService $directSapService
    ) {}

    /**
     * @param  list<string>  $accountCodes
     */
    public function validateCashierAccountCodes(string $transactionType, array $accountCodes): ?string
    {
        if ($transactionType === CashierBankTransactionDirectSapService::TRANSACTION_TYPE_INTEREST) {
            return $this->validateBankInterestAccountCodes($accountCodes);
        }

        return $this->directSapService->validateDebitAccountsForTransactionType($transactionType, $accountCodes);
    }

    /**
     * @param  list<string>  $accountCodes
     * @param  list<string>  $descriptions
     * @param  list<string>  $projects
     * @param  list<string>  $costCenters
     * @param  list<float|int|string>  $amounts
     * @return list<array<string, mixed>>
     */
    public function buildVerificationJournalDetailRows(
        string $transactionType,
        string $bankAccount,
        string $journalDescription,
        string $journalProject,
        string $bankLineCostCenter,
        array $accountCodes,
        array $descriptions,
        array $projects,
        array $costCenters,
        array $amounts,
    ): array {
        if ($transactionType === CashierBankTransactionDirectSapService::TRANSACTION_TYPE_INTEREST) {
            return $this->buildBankInterestDetailRows(
                $bankAccount,
                $journalDescription,
                $journalProject,
                $bankLineCostCenter,
                $accountCodes,
                $descriptions,
                $projects,
                $costCenters,
                $amounts,
            );
        }

        return $this->buildStandardOutflowDetailRows(
            $bankAccount,
            $journalDescription,
            $journalProject,
            $bankLineCostCenter,
            $accountCodes,
            $descriptions,
            $projects,
            $costCenters,
            $amounts,
        );
    }

    /**
     * @param  list<string>  $accountCodes
     */
    protected function validateBankInterestAccountCodes(array $accountCodes): ?string
    {
        $allowedSapAccounts = $this->directSapService->getAllowedSapAccountNumbers();
        if ($allowedSapAccounts === []) {
            return CashierBankTransactionDirectSapService::INVALID_ACCOUNT_MESSAGE;
        }

        $hasIncomeLine = false;

        foreach ($accountCodes as $accountCode) {
            $accountCode = (string) $accountCode;
            if (! $this->isBankInterestCashierAccount($accountCode)) {
                return CashierBankTransactionDirectSapService::INVALID_ACCOUNT_MESSAGE;
            }

            if (! in_array($accountCode, $allowedSapAccounts, true)) {
                return CashierBankTransactionDirectSapService::INVALID_ACCOUNT_MESSAGE;
            }

            if (str_starts_with($accountCode, self::BANK_INTEREST_INCOME_PREFIX)) {
                $hasIncomeLine = true;
            }
        }

        if (! $hasIncomeLine) {
            return self::BANK_INTEREST_MISSING_INCOME_MESSAGE;
        }

        return null;
    }

    protected function isBankInterestCashierAccount(string $accountCode): bool
    {
        return str_starts_with($accountCode, self::BANK_INTEREST_INCOME_PREFIX)
            || str_starts_with($accountCode, self::BANK_INTEREST_EXPENSE_PREFIX);
    }

    /**
     * @param  list<string>  $accountCodes
     * @param  list<string>  $descriptions
     * @param  list<string>  $projects
     * @param  list<string>  $costCenters
     * @param  list<float|int|string>  $amounts
     * @return list<array<string, mixed>>
     */
    protected function buildStandardOutflowDetailRows(
        string $bankAccount,
        string $journalDescription,
        string $journalProject,
        string $bankLineCostCenter,
        array $accountCodes,
        array $descriptions,
        array $projects,
        array $costCenters,
        array $amounts,
    ): array {
        $cashierTotal = array_sum($amounts);
        $rows = [
            [
                'account_code' => $bankAccount,
                'debit_credit' => 'credit',
                'description' => $journalDescription,
                'project' => $journalProject,
                'cost_center' => $bankLineCostCenter,
                'amount' => $cashierTotal,
            ],
        ];

        foreach ($accountCodes as $key => $accountCode) {
            $rows[] = [
                'account_code' => $accountCode,
                'debit_credit' => 'debit',
                'description' => $descriptions[$key],
                'project' => $projects[$key],
                'cost_center' => $costCenters[$key],
                'amount' => $amounts[$key],
            ];
        }

        return $rows;
    }

    /**
     * @param  list<string>  $accountCodes
     * @param  list<string>  $descriptions
     * @param  list<string>  $projects
     * @param  list<string>  $costCenters
     * @param  list<float|int|string>  $amounts
     * @return list<array<string, mixed>>
     */
    protected function buildBankInterestDetailRows(
        string $bankAccount,
        string $journalDescription,
        string $journalProject,
        string $bankLineCostCenter,
        array $accountCodes,
        array $descriptions,
        array $projects,
        array $costCenters,
        array $amounts,
    ): array {
        $interestTotals = $this->sumBankInterestCashierTotals($accountCodes, $amounts);
        $totalIncomeCredit = $interestTotals['income'];
        $totalExpenseDebit = $interestTotals['expense'];
        $cashierRows = [];

        foreach ($accountCodes as $key => $accountCode) {
            $amount = (float) $amounts[$key];
            $accountCode = (string) $accountCode;

            if (str_starts_with($accountCode, self::BANK_INTEREST_INCOME_PREFIX)) {
                $debitCredit = 'credit';
            } else {
                $debitCredit = 'debit';
            }

            $cashierRows[] = [
                'account_code' => $accountCode,
                'debit_credit' => $debitCredit,
                'description' => $descriptions[$key],
                'project' => $projects[$key],
                'cost_center' => $costCenters[$key],
                'amount' => $amounts[$key],
            ];
        }

        $rows = [];

        $netBankAmount = abs($totalIncomeCredit - $totalExpenseDebit);
        if ($netBankAmount > 0) {
            $bankDebitCredit = $totalIncomeCredit > $totalExpenseDebit ? 'debit' : 'credit';
            $rows[] = [
                'account_code' => $bankAccount,
                'debit_credit' => $bankDebitCredit,
                'description' => $journalDescription,
                'project' => $journalProject,
                'cost_center' => $bankLineCostCenter,
                'amount' => $netBankAmount,
            ];
        }

        return array_merge($rows, $cashierRows);
    }

    /**
     * @param  list<string>  $accountCodes
     * @param  list<float|int|string>  $amounts
     * @return array{income: float, expense: float}
     */
    protected function sumBankInterestCashierTotals(array $accountCodes, array $amounts): array
    {
        $income = 0.0;
        $expense = 0.0;

        foreach ($accountCodes as $key => $accountCode) {
            $amount = (float) ($amounts[$key] ?? 0);
            $accountCode = (string) $accountCode;

            if (str_starts_with($accountCode, self::BANK_INTEREST_INCOME_PREFIX)) {
                $income += $amount;
            } else {
                $expense += $amount;
            }
        }

        return [
            'income' => $income,
            'expense' => $expense,
        ];
    }

    /**
     * @param  list<string>  $accountCodes
     * @param  list<float|int|string>  $amounts
     */
    public function bankInterestJournalTotalFromCashierLines(array $accountCodes, array $amounts): float
    {
        $totals = $this->sumBankInterestCashierTotals($accountCodes, $amounts);

        return max($totals['income'], $totals['expense']);
    }

    /**
     * @param  list<string>  $accountCodes
     * @param  list<float|int|string>  $amounts
     */
    public function detailFooterTotalForTransactionType(string $transactionType, array $accountCodes, array $amounts): float
    {
        if ($transactionType === CashierBankTransactionDirectSapService::TRANSACTION_TYPE_INTEREST) {
            return $this->bankInterestJournalTotalFromCashierLines($accountCodes, $amounts);
        }

        $total = 0.0;
        foreach ($amounts as $amount) {
            $total += (float) $amount;
        }

        return $total;
    }

    /**
     * @param  list<array<string, mixed>>  $detailRows
     */
    public function totalDebitAmountFromDetailRows(array $detailRows): float
    {
        $total = 0.0;

        foreach ($detailRows as $row) {
            if (($row['debit_credit'] ?? '') === 'debit') {
                $total += (float) $row['amount'];
            }
        }

        return $total;
    }
}
