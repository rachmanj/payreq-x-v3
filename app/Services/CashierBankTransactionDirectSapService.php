<?php

namespace App\Services;

use App\Models\Parameter;
use App\Models\User;
use App\Models\VerificationJournal;
use Illuminate\Support\Collection;

class CashierBankTransactionDirectSapService
{
    public const TRANSACTION_TYPE_PETTY_CASH = 'transfer_to_petty_cash';

    public const TRANSACTION_TYPE_ADMIN_FEE = 'bank_admin_fee';

    public const TRANSACTION_TYPE_INTEREST = 'bank_interest';

    public const INVALID_ACCOUNT_MESSAGE = 'The selected account is not allowed for Bank Transaction. For vendor payments, expenses, or other accounts, please create a Payreq and Realization instead.';

    /**
     * @return array<string, list<string>>
     */
    public static function transactionTypeAccountMap(): array
    {
        return [
            self::TRANSACTION_TYPE_PETTY_CASH => ['11101005', '11101008', '11101010', '11101004', '11101006'],
            self::TRANSACTION_TYPE_ADMIN_FEE => ['71201001', '71201006', '71201007', '71201002'],
            self::TRANSACTION_TYPE_INTEREST => ['71101001'],
        ];
    }

    /**
     * @return list<string>
     */
    public function getAllowedSapAccountNumbers(): array
    {
        $parameter = Parameter::query()->where('name1', 'cashier_vj_sap_accounts')->first();
        if (! $parameter || trim((string) $parameter->param_value) === '') {
            return [];
        }

        return collect(explode(',', (string) $parameter->param_value))
            ->map(fn (string $code) => trim($code))
            ->filter()
            ->values()
            ->all();
    }

    public function getSapLimit(): int
    {
        $parameter = Parameter::query()->where('name1', 'cashier_vj_sap_limit')->first();
        if (! $parameter) {
            return 100_000_000;
        }

        return (int) $parameter->param_value;
    }

    /**
     * @return list<string>
     */
    public function allowedAccountsForTransactionType(string $transactionType): array
    {
        $typeAccounts = self::transactionTypeAccountMap()[$transactionType] ?? [];

        return array_values(array_intersect($typeAccounts, $this->getAllowedSapAccountNumbers()));
    }

    public function isEligibleForDirectSapSubmission(VerificationJournal $journal, User $user): bool
    {
        if (! $user->can('cashier_submit_vj_to_sap')) {
            return false;
        }

        if ($journal->type !== 'bank') {
            return false;
        }

        if ((int) $journal->amount > $this->getSapLimit()) {
            return false;
        }

        $details = $journal->relationLoaded('verificationJournalDetails')
            ? $journal->verificationJournalDetails
            : $journal->verificationJournalDetails()->get();

        return $this->linePatternMatchesDirectSapRules($journal, $details);
    }

    /**
     * @param  Collection<int, \App\Models\VerificationJournalDetail>|iterable  $details
     */
    public function linePatternMatchesDirectSapRules(VerificationJournal $journal, iterable $details): bool
    {
        $creditLines = collect($details)->where('debit_credit', 'credit');
        $debitLines = collect($details)->where('debit_credit', 'debit');

        if ($creditLines->count() !== 1) {
            return false;
        }

        $creditAccount = (string) $creditLines->first()->account_code;
        $bankAccount = (string) $journal->bank_account;

        if ($creditAccount !== $bankAccount) {
            return false;
        }

        if (! str_starts_with($creditAccount, '11201')) {
            return false;
        }

        if ($debitLines->isEmpty()) {
            return false;
        }

        $allowedAccounts = $this->getAllowedSapAccountNumbers();
        if ($allowedAccounts === []) {
            return false;
        }

        foreach ($debitLines as $debitLine) {
            if (! in_array((string) $debitLine->account_code, $allowedAccounts, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  list<string>  $debitAccountCodes
     */
    public function validateDebitAccountsForTransactionType(string $transactionType, array $debitAccountCodes): ?string
    {
        $allowedForType = $this->allowedAccountsForTransactionType($transactionType);
        if ($allowedForType === []) {
            return self::INVALID_ACCOUNT_MESSAGE;
        }

        foreach ($debitAccountCodes as $accountCode) {
            if (! in_array($accountCode, $allowedForType, true)) {
                return self::INVALID_ACCOUNT_MESSAGE;
            }
        }

        return null;
    }
}
