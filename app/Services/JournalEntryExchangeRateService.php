<?php

namespace App\Services;

use App\Models\ExchangeRate;
use Carbon\Carbon;
use DateTimeInterface;

class JournalEntryExchangeRateService
{
    public function defaultUsdToIdrRateForDate(DateTimeInterface|string $journalDate): ?string
    {
        $date = Carbon::parse($journalDate)->toDateString();

        return ExchangeRate::query()
            ->byCurrencyPair('USD', 'IDR')
            ->where('effective_date', '<=', $date)
            ->orderByDesc('effective_date')
            ->value('exchange_rate');
    }
}
