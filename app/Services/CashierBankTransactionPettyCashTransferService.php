<?php

namespace App\Services;

use App\Models\Account;
use App\Models\VerificationJournal;

class CashierBankTransactionPettyCashTransferService
{
    public function isTransferToPettyCash(VerificationJournal $journal): bool
    {
        if ($journal->type !== 'bank') {
            return false;
        }

        $project = $journal->project;
        if ($project === null || $project === '') {
            return false;
        }

        $cashAccountNumbers = Account::query()
            ->where('type', 'cash')
            ->where('project', $project)
            ->pluck('account_number')
            ->map(fn ($number) => trim((string) $number))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($cashAccountNumbers === []) {
            return false;
        }

        $journal->loadMissing('verificationJournalDetails');

        foreach ($journal->verificationJournalDetails as $detail) {
            $accountCode = trim((string) $detail->account_code);
            if ($accountCode !== '' && in_array($accountCode, $cashAccountNumbers, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    public function detailAccountTypesForProject(VerificationJournal $journal): array
    {
        $project = $journal->project;
        if ($project === null || $project === '') {
            return [];
        }

        $journal->loadMissing('verificationJournalDetails');
        $codes = $journal->verificationJournalDetails
            ->pluck('account_code')
            ->map(fn ($code) => trim((string) $code))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($codes === []) {
            return [];
        }

        $typesByNumber = Account::query()
            ->where('project', $project)
            ->whereIn('account_number', $codes)
            ->pluck('type', 'account_number');

        $types = [];
        foreach ($codes as $code) {
            $type = $typesByNumber->get($code);
            $types[] = $type !== null ? (string) $type : 'unknown';
        }

        return array_values(array_unique($types));
    }
}
