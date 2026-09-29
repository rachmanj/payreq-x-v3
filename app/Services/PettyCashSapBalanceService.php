<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

final class PettyCashSapBalanceService
{
    private const CACHE_TTL_SECONDS = 300;

    public function __construct(
        private SapService $sapService,
        private PettyCashAccountResolver $pettyCashAccountResolver,
    ) {}

    /**
     * @return array{balance: ?float, available: bool, sap_account: ?string}
     */
    public function getBalanceForProject(string $project, bool $forceRefresh = false): array
    {
        $cashAccount = $this->pettyCashAccountResolver->resolveForProject($project);
        $sapAccountCode = $cashAccount?->sap_account !== null && trim((string) $cashAccount->sap_account) !== ''
            ? trim((string) $cashAccount->sap_account)
            : null;

        if ($sapAccountCode === null) {
            return [
                'balance' => null,
                'available' => false,
                'sap_account' => null,
            ];
        }

        $cacheKey = $this->cacheKey($project, $sapAccountCode);

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        if (! $forceRefresh) {
            $cached = Cache::get($cacheKey);
            if (is_array($cached)) {
                return $cached;
            }
        }

        try {
            $balance = $this->sapService->getChartOfAccountSystemBalance($sapAccountCode);
        } catch (\Throwable $exception) {
            report($exception);

            return [
                'balance' => null,
                'available' => false,
                'sap_account' => $sapAccountCode,
            ];
        }

        if ($balance === null) {
            return [
                'balance' => null,
                'available' => false,
                'sap_account' => $sapAccountCode,
            ];
        }

        $result = [
            'balance' => $balance,
            'available' => true,
            'sap_account' => $sapAccountCode,
        ];

        Cache::put($cacheKey, $result, self::CACHE_TTL_SECONDS);

        return $result;
    }

    public function forgetCacheForProject(string $project): void
    {
        $cashAccount = $this->pettyCashAccountResolver->resolveForProject($project);
        $sapAccountCode = $cashAccount?->sap_account;

        if ($sapAccountCode === null || trim((string) $sapAccountCode) === '') {
            return;
        }

        Cache::forget($this->cacheKey($project, trim((string) $sapAccountCode)));
    }

    private function cacheKey(string $project, string $sapAccountCode): string
    {
        return "petty_cash_sap_balance_{$project}_{$sapAccountCode}";
    }
}
