<?php

namespace App\Services;

use App\Models\Parameter;
use Illuminate\Support\Facades\Cache;

final class ClearingAccountMonitorService
{
    private const CACHE_TTL = 300;

    public function __construct(private SapService $sapService) {}

    /**
     * @return array<int, string>
     */
    public function getMonitoredAccountCodes(): array
    {
        $parameter = Parameter::query()
            ->where('name1', 'dashboard_clearing_accounts')
            ->where('name2', 'ALL')
            ->first();

        if ($parameter === null || trim((string) $parameter->param_value) === '') {
            return [];
        }

        return $this->parseAccountCodes((string) $parameter->param_value);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getCards(): array
    {
        $codes = $this->getMonitoredAccountCodes();

        if ($codes === []) {
            return [];
        }

        $today = now()->format('Y-m-d');

        return array_map(function (string $code) use ($today) {
            return $this->buildCard($code, $today);
        }, $codes);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildCard(string $code, string $date): array
    {
        $cacheKey = "clearing_account_{$code}_{$date}";

        try {
            $statement = Cache::remember(
                $cacheKey,
                self::CACHE_TTL,
                fn () => $this->sapService->getAccountStatement($code, $date, $date),
            );

            $summary = data_get($statement, 'summary', []);
            $totalDebit = (float) data_get($summary, 'total_debit', 0);
            $totalCredit = (float) data_get($summary, 'total_credit', 0);

            return [
                'code' => $code,
                'name' => data_get($statement, 'account.name', $code),
                'balance' => (float) data_get($statement, 'closing_balance', 0),
                'today_debit' => $totalDebit,
                'today_credit' => $totalCredit,
                'today_net' => $totalDebit - $totalCredit,
                'today_count' => (int) data_get($summary, 'transaction_count', 0),
                'error' => null,
            ];
        } catch (\Throwable $exception) {
            report($exception);

            return [
                'code' => $code,
                'name' => $code,
                'balance' => null,
                'today_debit' => null,
                'today_credit' => null,
                'today_net' => null,
                'today_count' => null,
                'error' => $this->shortErrorMessage($exception),
            ];
        }
    }

    /**
     * @return array<int, string>
     */
    public function parseAccountCodes(string $raw): array
    {
        return collect(explode(',', $raw))
            ->map(fn (string $code) => trim($code))
            ->filter(fn (string $code) => $code !== '')
            ->unique()
            ->values()
            ->all();
    }

    private function shortErrorMessage(\Throwable $exception): string
    {
        $message = trim($exception->getMessage());

        if ($message === '') {
            return 'Gagal mengambil data SAP.';
        }

        return strlen($message) > 120 ? substr($message, 0, 117).'...' : $message;
    }
}
