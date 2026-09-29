<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Parameter;

final class PettyCashAccountResolver
{
    public const PARAMETER_NAME = 'petty_cash_account';

    public function resolveForProject(string $project): ?Account
    {
        $configuredSapAccount = Parameter::query()
            ->where('name1', self::PARAMETER_NAME)
            ->where('name2', $project)
            ->value('param_value');

        if ($configuredSapAccount !== null && trim((string) $configuredSapAccount) !== '') {
            $account = Account::query()
                ->where('type', 'cash')
                ->where('project', $project)
                ->where('sap_account', trim((string) $configuredSapAccount))
                ->orderBy('id')
                ->first();

            if ($account !== null) {
                return $account;
            }
        }

        return Account::query()
            ->where('type', 'cash')
            ->where('project', $project)
            ->orderBy('id')
            ->first();
    }
}
