<?php

namespace App\Services;

class FakturPpnCalculationService
{
    /** @var array<string, float> */
    public const VAT_CODE_RATES = [
        'A100' => 0.0,
        'A110' => 10.0,
        'A111' => 11.0,
        'A112' => 12.0,
        'B100' => 0.0,
        'B101' => 1.0,
        'B101.1' => 1.1,
        'B101.2' => 1.2,
        'B107' => 7.0,
        'B110' => 10.0,
        'B111' => 11.0,
        'B112' => 12.0,
    ];

    /**
     * @return array{
     *     ppn_rate: ?float,
     *     dpp: float,
     *     dpp_calculated: ?float,
     *     dpp_source: ?string,
     *     validation_status: string,
     *     review_note: ?string
     * }
     */
    public function calculateFromPpnAmount(float $ppn, ?string $taxCode = null, ?string $remarks = null): array
    {
        $ppn = round($ppn, 2);

        if ($ppn <= 0) {
            return $this->needsReview($ppn, 'PPN nol atau negatif; tidak dapat menghitung DPP.');
        }

        $resolvedCode = $taxCode ? strtoupper(trim($taxCode)) : $this->extractTaxCodeFromText($remarks);
        if ($resolvedCode !== null && isset(self::VAT_CODE_RATES[$resolvedCode])) {
            return $this->fromKnownRate($ppn, self::VAT_CODE_RATES[$resolvedCode], 'gl');
        }

        $primaryCandidates = $this->findRateCandidates($ppn, [11.0, 12.0]);
        if (count($primaryCandidates) === 1) {
            return $this->fromKnownRate($ppn, $primaryCandidates[0], 'formula');
        }
        if (count($primaryCandidates) > 1) {
            $rates = implode('%, ', array_map(fn (float $r) => (string) $r, $primaryCandidates));

            return $this->needsReview(
                $ppn,
                "Beberapa tarif utama menghasilkan DPP bulat ({$rates}%); perlu pemeriksaan manusia (mis. DPP nilai lain 12% vs tarif 11%)."
            );
        }

        $secondaryCandidates = $this->findRateCandidates($ppn, [10.0, 7.0]);
        if (count($secondaryCandidates) === 1) {
            return $this->fromKnownRate($ppn, $secondaryCandidates[0], 'formula');
        }
        if (count($secondaryCandidates) > 1) {
            $rates = implode('%, ', array_map(fn (float $r) => (string) $r, $secondaryCandidates));

            return $this->needsReview(
                $ppn,
                "Beberapa tarif menghasilkan DPP bulat ({$rates}%); perlu pemeriksaan manusia."
            );
        }

        return $this->needsReview($ppn, 'Tarif PPN tidak dapat ditentukan dari nominal GL saja.');
    }

    public function masaPajakFromDate(?string $date): ?string
    {
        if ($date === null || $date === '') {
            return null;
        }

        return substr($date, 0, 7);
    }

    /**
     * @return array{
     *     ppn_rate: ?float,
     *     dpp: float,
     *     dpp_calculated: ?float,
     *     dpp_source: ?string,
     *     validation_status: string,
     *     review_note: ?string
     * }
     */
    private function fromKnownRate(float $ppn, float $ratePercent, string $source): array
    {
        if ($ratePercent <= 0.0) {
            return [
                'ppn_rate' => $ratePercent,
                'dpp' => 0.0,
                'dpp_calculated' => 0.0,
                'dpp_source' => $source,
                'validation_status' => 'belum_diperiksa',
                'review_note' => null,
            ];
        }

        $dpp = round($ppn / ($ratePercent / 100), 2);

        return [
            'ppn_rate' => $ratePercent,
            'dpp' => $dpp,
            'dpp_calculated' => $dpp,
            'dpp_source' => $source,
            'validation_status' => 'belum_diperiksa',
            'review_note' => null,
        ];
    }

    /**
     * @return array{
     *     ppn_rate: ?float,
     *     dpp: float,
     *     dpp_calculated: ?float,
     *     dpp_source: ?string,
     *     validation_status: string,
     *     review_note: ?string
     * }
     */
    private function needsReview(float $ppn, string $note): array
    {
        return [
            'ppn_rate' => null,
            'dpp' => round($ppn / 0.11, 2),
            'dpp_calculated' => null,
            'dpp_source' => null,
            'validation_status' => 'belum_diperiksa',
            'review_note' => $note,
        ];
    }

    /**
     * @param  list<float>  $rates
     * @return list<float>
     */
    private function findRateCandidates(float $ppn, array $rates): array
    {
        $matches = [];

        foreach ($rates as $rate) {
            if ($rate <= 0.0) {
                continue;
            }

            $dpp = $ppn / ($rate / 100);
            if ($this->isWholeRupiah($dpp)) {
                $matches[] = $rate;
            }
        }

        return $matches;
    }

    private function isWholeRupiah(float $amount): bool
    {
        return abs($amount - round($amount, 0)) < 0.01;
    }

    private function extractTaxCodeFromText(?string $text): ?string
    {
        if ($text === null || trim($text) === '') {
            return null;
        }

        if (preg_match('/\b([AB]\d{3}(?:\.\d)?)\b/i', $text, $m) !== 1) {
            return null;
        }

        return strtoupper($m[1]);
    }
}
