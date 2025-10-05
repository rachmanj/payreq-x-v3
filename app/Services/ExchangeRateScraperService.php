<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ExchangeRateScraperService
{
    private string $baseUrl = 'https://fiskal.kemenkeu.go.id/informasi-publik/kurs-pajak';

    public function fetch(?array $currencies = null): array
    {
        $response = Http::timeout(30)->get($this->baseUrl);
        if (!$response->successful()) {
            throw new \RuntimeException('Failed to fetch Kemenkeu kurs pajak page: ' . $response->status());
        }

        $html = $response->body();

        $kmk = $this->parseKmkInfo($html);
        $rates = $this->parseRates($html, $currencies ?? config('exchange_rates.target_currencies'));

        return [
            'kmk' => $kmk,
            'rates' => $rates,
            'scraped_at' => now(),
        ];
    }

    private function parseKmkInfo(string $html): array
    {
        $kmkNumber = null;
        $effectiveFrom = null;
        $effectiveTo = null;

        if (preg_match('/KMK\s+Nomor\s+([^<]+)</u', $html, $m)) {
            $kmkNumber = trim($m[1]);
        }

        if (preg_match('/Tanggal Berlaku:\s*(\d{2})\s*(\w+)\s*(\d{4})\s*-\s*(\d{2})\s*(\w+)\s*(\d{4})/u', $html, $m)) {
            $from = $this->parseIndonesianDate($m[1], $m[2], $m[3]);
            $to = $this->parseIndonesianDate($m[4], $m[5], $m[6]);
            $effectiveFrom = $from->toDateString();
            $effectiveTo = $to->toDateString();
        }

        return [
            'kmk_number' => $kmkNumber,
            'effective_from' => $effectiveFrom,
            'effective_to' => $effectiveTo,
        ];
    }

    private function parseRates(string $html, array $targetCurrencies): array
    {
        $rows = [];

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();
        $xpath = new \DOMXPath($dom);

        // Find all table rows; Kemenkeu page has a main table under Kurs Pajak
        $trNodes = $xpath->query('//table//tr');
        foreach ($trNodes as $tr) {
            $tds = $xpath->query('td', $tr);
            if (!$tds || $tds->length < 3) {
                continue; // skip header or malformed rows
            }

            // Column 2: currency with code in parentheses, e.g., "Dolar Singapura (SGD) SGD"
            $currencyCell = trim($tds->item(1)->textContent);
            if (!preg_match('/\b([A-Z]{3})\b/', $currencyCell, $m)) {
                continue;
            }
            $currencyCode = strtoupper($m[1]);
            if (!in_array($currencyCode, $targetCurrencies)) {
                continue;
            }

            // Column 3: value cell may include <img> and extra tags; use textContent and normalize
            $valueCell = trim($tds->item(2)->textContent);
            $numeric = $this->normalizeIndonesianNumber($valueCell);
            if ($numeric === null) {
                continue;
            }

            $isJpyPer100 = $currencyCode === 'JPY';
            $rateIdrPerUnit = $isJpyPer100 ? ($numeric / 100.0) : $numeric;

            $rows[] = [
                'currency_code' => $currencyCode,
                'rate_to_idr' => round($rateIdrPerUnit, 6),
                'raw_value' => $valueCell,
                'is_jpy_per_100' => $isJpyPer100,
            ];
        }

        return $rows;
    }

    private function normalizeIndonesianNumber(string $text): ?float
    {
        $clean = preg_replace('/[^0-9.,-]/', '', $text);
        if ($clean === null || $clean === '') {
            return null;
        }

        $clean = str_replace('.', '', $clean);
        $clean = str_replace(',', '.', $clean);
        if (!is_numeric($clean)) {
            return null;
        }
        return (float) $clean;
    }

    private function parseIndonesianDate(string $day, string $monthName, string $year): Carbon
    {
        $map = [
            'Januari' => 1,
            'Februari' => 2,
            'Maret' => 3,
            'April' => 4,
            'Mei' => 5,
            'Juni' => 6,
            'Juli' => 7,
            'Agustus' => 8,
            'September' => 9,
            'Oktober' => 10,
            'November' => 11,
            'Desember' => 12,
        ];
        $month = $map[$monthName] ?? null;
        if ($month === null) {
            throw new \InvalidArgumentException('Unknown month name: ' . $monthName);
        }
        return Carbon::createFromDate((int) $year, (int) $month, (int) $day);
    }
}
