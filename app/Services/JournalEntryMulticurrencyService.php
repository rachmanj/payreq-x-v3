<?php

namespace App\Services;

use App\Models\JournalEntryLine;
use Illuminate\Support\Collection;

class JournalEntryMulticurrencyService
{
    public const SUPPORTED_FOREIGN_CURRENCY = 'USD';

    public const MIXED_IDR_AND_FOREIGN_CURRENCY_ERROR = 'SAP tidak menerima jurnal yang mencampur valas (USD) dan IDR dalam satu dokumen. Pisahkan menjadi dua jurnal: satu jurnal valas (USD) dan satu jurnal IDR.';

    private const BALANCE_TOLERANCE = 0.01;

    public function calculateIdrAmount(float $fcAmount, float $exchangeRate): float
    {
        return round($fcAmount * $exchangeRate, 2);
    }

    /**
     * @param  array<int, array<string, mixed>>  $lines
     * @return array<int, array<string, mixed>>
     */
    public function normalizeLines(array $lines): array
    {
        return array_map(function (array $line): array {
            $currency = $this->resolveCurrency($line);

            if ($currency === 'IDR') {
                $line['currency'] = 'IDR';
                $line['fc_amount'] = null;
                $line['exchange_rate'] = null;

                return $line;
            }

            if ($currency === self::SUPPORTED_FOREIGN_CURRENCY) {
                $fcAmount = (float) ($line['fc_amount'] ?? 0);
                $exchangeRate = (float) ($line['exchange_rate'] ?? 0);

                if ($fcAmount > 0 && $exchangeRate > 0) {
                    $line['currency'] = self::SUPPORTED_FOREIGN_CURRENCY;
                    $line['amount'] = $this->calculateIdrAmount($fcAmount, $exchangeRate);
                }

                return $line;
            }

            return $line;
        }, $lines);
    }

    /**
     * @param  array<int, array<string, mixed>>|Collection<int, JournalEntryLine>  $lines
     * @return array<int, string>
     */
    public function validateLines(array|Collection $lines): array
    {
        $lineArrays = $this->linesToArrays($lines);
        $errors = [];

        foreach ($lineArrays as $index => $line) {
            $currency = $this->resolveCurrency($line);

            if (! in_array($currency, ['IDR', self::SUPPORTED_FOREIGN_CURRENCY], true)) {
                $errors[] = 'Baris valas hanya mendukung USD untuk saat ini';

                continue;
            }

            if ($currency === self::SUPPORTED_FOREIGN_CURRENCY) {
                $fcAmount = (float) ($line['fc_amount'] ?? 0);
                $exchangeRate = (float) ($line['exchange_rate'] ?? 0);

                if ($fcAmount <= 0) {
                    $errors[] = sprintf(
                        'Baris %d (USD): nominal valas wajib diisi dan harus lebih dari 0.',
                        $index + 1
                    );
                }

                if ($exchangeRate <= 0) {
                    $errors[] = sprintf(
                        'Baris %d (USD): kurs wajib diisi dan harus lebih dari 0.',
                        $index + 1
                    );
                }

                if ($fcAmount > 0 && $exchangeRate > 0) {
                    $expectedIdr = $this->calculateIdrAmount($fcAmount, $exchangeRate);
                    $actualIdr = (float) ($line['amount'] ?? 0);

                    if (abs($expectedIdr - $actualIdr) > self::BALANCE_TOLERANCE) {
                        $errors[] = sprintf(
                            'Baris %d (USD): nilai IDR harus %s (nominal valas × kurs).',
                            $index + 1,
                            number_format($expectedIdr, 2, '.', '')
                        );
                    }
                }
            } elseif ($currency === 'IDR') {
                $amount = (float) ($line['amount'] ?? 0);
                if ($amount < self::BALANCE_TOLERANCE) {
                    $errors[] = sprintf(
                        'Baris %d (IDR): nominal wajib diisi dan harus lebih dari 0.',
                        $index + 1
                    );
                }
            }
        }

        if (! empty($errors)) {
            return array_values(array_unique($errors));
        }

        $hasIdrLine = false;
        $hasForeignCurrencyLine = false;

        foreach ($lineArrays as $line) {
            $currency = $this->resolveCurrency($line);

            if ($currency === 'IDR') {
                $hasIdrLine = true;
            }

            if ($currency === self::SUPPORTED_FOREIGN_CURRENCY) {
                $hasForeignCurrencyLine = true;
            }
        }

        if ($hasIdrLine && $hasForeignCurrencyLine) {
            return [self::MIXED_IDR_AND_FOREIGN_CURRENCY_ERROR];
        }

        $totalDebit = collect($lineArrays)
            ->where('debit_credit', 'debit')
            ->sum(fn (array $line) => (float) ($line['amount'] ?? 0));

        $totalCredit = collect($lineArrays)
            ->where('debit_credit', 'credit')
            ->sum(fn (array $line) => (float) ($line['amount'] ?? 0));

        $idrDifference = abs($totalDebit - $totalCredit);
        if ($idrDifference > self::BALANCE_TOLERANCE) {
            $errors[] = sprintf(
                'Jurnal tidak seimbang dalam IDR. Total debit: %s, total kredit: %s, selisih: %s.',
                number_format($totalDebit, 2, ',', '.'),
                number_format($totalCredit, 2, ',', '.'),
                number_format($idrDifference, 2, ',', '.')
            );
        }

        $foreignCurrencies = collect($lineArrays)
            ->pluck('currency')
            ->map(fn ($c) => $this->resolveCurrency(['currency' => $c]))
            ->filter(fn (string $c) => $c !== 'IDR')
            ->unique();

        foreach ($foreignCurrencies as $foreignCurrency) {
            $foreignLines = collect($lineArrays)->filter(
                fn (array $line) => $this->resolveCurrency($line) === $foreignCurrency
            );

            $fcDebit = $foreignLines
                ->where('debit_credit', 'debit')
                ->sum(fn (array $line) => (float) ($line['fc_amount'] ?? 0));

            $fcCredit = $foreignLines
                ->where('debit_credit', 'credit')
                ->sum(fn (array $line) => (float) ($line['fc_amount'] ?? 0));

            $fcDifference = abs($fcDebit - $fcCredit);
            if ($fcDifference > self::BALANCE_TOLERANCE) {
                $errors[] = sprintf(
                    'Jurnal tidak seimbang untuk mata uang %s. Total debit: %s, total kredit: %s, selisih: %s.',
                    $foreignCurrency,
                    number_format($fcDebit, 2, ',', '.'),
                    number_format($fcCredit, 2, ',', '.'),
                    number_format($fcDifference, 2, ',', '.')
                );
            }
        }

        return $errors;
    }

    /**
     * @param  array<int, array<string, mixed>>  $lines
     */
    public function entryHasForeignCurrency(array $lines): bool
    {
        foreach ($lines as $line) {
            if ($this->resolveCurrency($line) === self::SUPPORTED_FOREIGN_CURRENCY) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $line
     */
    public function resolveCurrency(array $line): string
    {
        return strtoupper((string) ($line['currency'] ?? 'IDR'));
    }

    /**
     * @param  array<int, array<string, mixed>>|Collection<int, JournalEntryLine>  $lines
     * @return array<int, array<string, mixed>>
     */
    protected function linesToArrays(array|Collection $lines): array
    {
        if ($lines instanceof Collection) {
            return $lines->map(function (JournalEntryLine $line): array {
                return [
                    'account_code' => $line->account_code,
                    'debit_credit' => $line->debit_credit,
                    'amount' => $line->amount,
                    'currency' => $line->currency ?? 'IDR',
                    'fc_amount' => $line->fc_amount,
                    'exchange_rate' => $line->exchange_rate,
                    'project' => $line->project,
                    'cost_center' => $line->cost_center,
                    'description' => $line->description,
                ];
            })->values()->all();
        }

        return array_values($lines);
    }
}
