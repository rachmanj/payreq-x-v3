<?php

namespace App\Console\Commands;

use App\Models\Currency;
use App\Models\ExchangeRate;
use App\Models\User;
use App\Services\ExchangeRateScraperService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class UpdateExchangeRates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'exchange-rates:update {--force : Force update even if exists} {--currencies= : Comma-separated list of currency codes (e.g., USD,SGD) } {--no-expand : Do not create daily records for full KMK range}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch Kemenkeu Kurs Pajak and upsert into exchange_rates';

    /**
     * Execute the console command.
     */
    public function handle(ExchangeRateScraperService $scraper)
    {
        $this->info('Fetching exchange rates from Kemenkeu...');

        try {
            $currenciesOpt = $this->option('currencies');
            $currencies = null;
            if (is_string($currenciesOpt) && trim($currenciesOpt) !== '') {
                $currencies = collect(explode(',', $currenciesOpt))
                    ->map(fn ($c) => strtoupper(trim($c)))
                    ->filter()
                    ->values()
                    ->all();
                if (empty($currencies)) {
                    $currencies = null;
                }
            }

            $data = $scraper->fetch($currencies);
            $kmk = $data['kmk'];
            $rates = $data['rates'];

            if (! $kmk['effective_from'] || ! $kmk['effective_to']) {
                $this->warn('Effective date range not found on source page. Aborting.');

                return 1;
            }

            $effectiveFrom = Carbon::parse($kmk['effective_from']);
            $effectiveTo = Carbon::parse($kmk['effective_to']);

            $actorId = $this->resolveActorUserId();
            if ($actorId === null) {
                $this->warn('No user found in the database. Create at least one user (or run seeds), then retry.');

                return 1;
            }

            $codesForFk = collect($rates)->pluck('currency_code')->push('IDR')->unique()->values()->all();
            $this->ensureCurrencyRowsExist($codesForFk, $actorId);

            $alreadyExists = ExchangeRate::where('currency_to', 'IDR')
                ->whereDate('effective_date', $effectiveFrom->toDateString())
                ->exists();

            if ($alreadyExists && ! $this->option('force')) {
                $this->info('Rates for this period already exist. Use --force to re-import.');

                return 0;
            }

            $created = 0;
            foreach ($rates as $row) {
                $currency = $row['currency_code'];
                $rateIdrPerUnit = $row['rate_to_idr'];

                // compute change based on latest previous record before this KMK period
                $previous = ExchangeRate::where('currency_from', $currency)
                    ->where('currency_to', 'IDR')
                    ->whereDate('effective_date', '<', $effectiveFrom->toDateString())
                    ->orderByDesc('effective_date')
                    ->first();
                $change = $previous ? round($rateIdrPerUnit - (float) $previous->exchange_rate, 6) : null;

                if ($this->option('no-expand')) {
                    ExchangeRate::updateOrCreate(
                        [
                            'currency_from' => $currency,
                            'currency_to' => 'IDR',
                            'effective_date' => $effectiveFrom->toDateString(),
                        ],
                        [
                            'exchange_rate' => $rateIdrPerUnit,
                            'created_by' => $actorId,
                            'updated_by' => Auth::id(),
                            'kmk_number' => $kmk['kmk_number'] ?? null,
                            'kmk_effective_from' => $effectiveFrom->toDateString(),
                            'kmk_effective_to' => $effectiveTo->toDateString(),
                            'source' => 'automated',
                            'change_from_previous' => $change,
                            'scraped_at' => now(),
                        ]
                    );
                    $created++;
                } else {
                    // expand across full KMK effective date range
                    $cursor = $effectiveFrom->copy();
                    while ($cursor->lte($effectiveTo)) {
                        ExchangeRate::updateOrCreate(
                            [
                                'currency_from' => $currency,
                                'currency_to' => 'IDR',
                                'effective_date' => $cursor->toDateString(),
                            ],
                            [
                                'exchange_rate' => $rateIdrPerUnit,
                                'created_by' => $actorId,
                                'updated_by' => Auth::id(),
                                'kmk_number' => $kmk['kmk_number'] ?? null,
                                'kmk_effective_from' => $effectiveFrom->toDateString(),
                                'kmk_effective_to' => $effectiveTo->toDateString(),
                                'source' => 'automated',
                                'change_from_previous' => $change,
                                'scraped_at' => now(),
                            ]
                        );
                        $created++;
                        $cursor->addDay();
                    }
                }
            }

            $this->info("Upserted {$created} exchange rates for KMK period {$effectiveFrom->toDateString()} to {$effectiveTo->toDateString()} (KMK: ".($kmk['kmk_number'] ?? '-').')');

            return 0;
        } catch (\Throwable $e) {
            Log::error('exchange-rates:update failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->error('Failed: '.$e->getMessage());

            return 1;
        }
    }

    private function resolveActorUserId(): ?int
    {
        $authId = Auth::id();
        if ($authId !== null) {
            return (int) $authId;
        }

        /** @var int|null $first */
        $first = User::query()->orderBy('id')->value('id');

        return $first !== null ? (int) $first : null;
    }

    /**
     * @param  list<string>  $currencyCodes
     */
    private function ensureCurrencyRowsExist(array $currencyCodes, int $createdBy): void
    {
        foreach ($currencyCodes as $code) {
            $code = strtoupper(trim($code));
            if ($code === '') {
                continue;
            }

            ['currency_name' => $name, 'symbol' => $symbol] = $this->defaultsForImportedCurrency($code);

            Currency::query()->firstOrCreate(
                ['currency_code' => $code],
                [
                    'currency_name' => $name,
                    'symbol' => $symbol,
                    'is_active' => true,
                    'created_by' => $createdBy,
                ]
            );
        }
    }

    /**
     * @return array{currency_name: string, symbol: ?string}
     */
    private function defaultsForImportedCurrency(string $currencyCode): array
    {
        $map = [
            'IDR' => ['currency_name' => 'Indonesian Rupiah', 'symbol' => 'Rp'],
            'USD' => ['currency_name' => 'US Dollar', 'symbol' => '$'],
            'EUR' => ['currency_name' => 'Euro', 'symbol' => '€'],
            'SGD' => ['currency_name' => 'Singapore Dollar', 'symbol' => 'S$'],
            'JPY' => ['currency_name' => 'Japanese Yen', 'symbol' => '¥'],
            'GBP' => ['currency_name' => 'British Pound', 'symbol' => '£'],
            'AUD' => ['currency_name' => 'Australian Dollar', 'symbol' => 'A$'],
            'CAD' => ['currency_name' => 'Canadian Dollar', 'symbol' => 'C$'],
            'CNY' => ['currency_name' => 'Chinese Yuan', 'symbol' => '¥'],
            'KRW' => ['currency_name' => 'South Korean Won', 'symbol' => '₩'],
            'MYR' => ['currency_name' => 'Malaysian Ringgit', 'symbol' => 'RM'],
            'CHF' => ['currency_name' => 'Swiss Franc', 'symbol' => null],
            'DKK' => ['currency_name' => 'Danish Krone', 'symbol' => 'kr'],
            'HKD' => ['currency_name' => 'Hong Kong Dollar', 'symbol' => 'HK$'],
            'NZD' => ['currency_name' => 'New Zealand Dollar', 'symbol' => 'NZ$'],
            'NOK' => ['currency_name' => 'Norwegian Krone', 'symbol' => 'kr'],
            'SEK' => ['currency_name' => 'Swedish Krona', 'symbol' => 'kr'],
            'SAR' => ['currency_name' => 'Saudi Riyal', 'symbol' => null],
            'THB' => ['currency_name' => 'Thai Baht', 'symbol' => '฿'],
        ];

        if (isset($map[$currencyCode])) {
            return $map[$currencyCode];
        }

        return [
            'currency_name' => sprintf('%s (KMK import)', $currencyCode),
            'symbol' => null,
        ];
    }
}
