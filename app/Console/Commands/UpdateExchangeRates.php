<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ExchangeRateScraperService;
use App\Models\ExchangeRate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

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
                    ->map(fn($c) => strtoupper(trim($c)))
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

            if (!$kmk['effective_from'] || !$kmk['effective_to']) {
                $this->warn('Effective date range not found on source page. Aborting.');
                return 1;
            }

            $effectiveFrom = Carbon::parse($kmk['effective_from']);
            $effectiveTo = Carbon::parse($kmk['effective_to']);

            $alreadyExists = ExchangeRate::where('currency_to', 'IDR')
                ->whereDate('effective_date', $effectiveFrom->toDateString())
                ->exists();

            if ($alreadyExists && !$this->option('force')) {
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
                            'created_by' => Auth::id() ?? 1,
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
                                'created_by' => Auth::id() ?? 1,
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

            $this->info("Upserted {$created} exchange rates for KMK period {$effectiveFrom->toDateString()} to {$effectiveTo->toDateString()} (KMK: " . ($kmk['kmk_number'] ?? '-') . ")");
            return 0;
        } catch (\Throwable $e) {
            Log::error('exchange-rates:update failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->error('Failed: ' . $e->getMessage());
            return 1;
        }
    }
}
