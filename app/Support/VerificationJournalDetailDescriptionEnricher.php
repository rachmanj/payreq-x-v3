<?php

namespace App\Support;

use App\Models\Account;
use App\Models\Realization;
use App\Models\RealizationDetail;
use App\Models\VerificationJournalDetail;

class VerificationJournalDetailDescriptionEnricher
{
    public static function baseDescription(?string $description): string
    {
        if ($description === null || $description === '') {
            return '';
        }

        return trim((string) preg_replace('/\n?\[Unit: [^\]]+\]/', '', $description));
    }

    public static function displayDescription(VerificationJournalDetail $detail): string
    {
        $baseDescription = self::baseDescription($detail->description);

        if (! $detail->realization_no || $detail->debit_credit !== 'debit') {
            return $baseDescription;
        }

        $realization = Realization::where('nomor', $detail->realization_no)->first();

        if (! $realization) {
            return $baseDescription;
        }

        $matchedDetail = self::matchedRealizationDetail($detail, $realization);

        if (! $matchedDetail) {
            return $baseDescription;
        }

        $additionalInfo = self::buildAdditionalInfo($matchedDetail);

        if ($additionalInfo === []) {
            return $baseDescription;
        }

        return $baseDescription."\n[".implode(' | ', $additionalInfo).']';
    }

    public static function enrich(VerificationJournalDetail $detail): VerificationJournalDetail
    {
        $detail->description = self::displayDescription($detail);

        return $detail;
    }

    /**
     * @return array<int, string>
     */
    public static function buildAdditionalInfo(RealizationDetail $detail): array
    {
        $additionalInfo = [];

        if (! empty($detail->unit_no)) {
            $additionalInfo[] = 'Unit: '.$detail->unit_no;
        }

        if (! empty($detail->nopol)) {
            $additionalInfo[] = 'Nopol: '.$detail->nopol;
        }

        if (! empty($detail->type)) {
            $additionalInfo[] = 'Type: '.$detail->type;
        }

        if (! empty($detail->qty)) {
            $qtyLabel = 'Qty: '.$detail->qty;

            if (! empty($detail->uom)) {
                $qtyLabel .= ' '.$detail->uom;
            }

            $additionalInfo[] = $qtyLabel;
        }

        if (! empty($detail->km_position)) {
            $additionalInfo[] = 'HM: '.$detail->km_position;
        }

        return $additionalInfo;
    }

    public static function matchedRealizationDetail(
        VerificationJournalDetail $detail,
        ?Realization $realization = null
    ): ?RealizationDetail {
        if (! $detail->realization_no || $detail->debit_credit !== 'debit') {
            return null;
        }

        $realization ??= Realization::where('nomor', $detail->realization_no)->first();

        if (! $realization) {
            return null;
        }

        return self::resolveMatchedRealizationDetail($detail, $realization);
    }

    private static function resolveMatchedRealizationDetail(
        VerificationJournalDetail $detail,
        Realization $realization
    ): ?RealizationDetail {
        $realizationDetails = RealizationDetail::where('realization_id', $realization->id)->get();

        foreach ($realizationDetails as $realizationDetail) {
            $accountNumber = self::accountNumberForDetail($realizationDetail);

            if (
                $accountNumber === $detail->account_code &&
                (stripos($detail->description, $realizationDetail->description) !== false ||
                    stripos($realizationDetail->description, $detail->description) !== false)
            ) {
                return $realizationDetail;
            }
        }

        foreach ($realizationDetails as $realizationDetail) {
            if (self::accountNumberForDetail($realizationDetail) === $detail->account_code) {
                return $realizationDetail;
            }
        }

        if ($realizationDetails->count() === 1) {
            return $realizationDetails->first();
        }

        return null;
    }

    private static function accountNumberForDetail(RealizationDetail $detail): ?string
    {
        if (! $detail->account_id) {
            return null;
        }

        $account = Account::find($detail->account_id);

        return $account?->account_number;
    }
}
