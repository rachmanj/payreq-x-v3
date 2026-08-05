<?php

namespace App\Support;

use App\Models\Account;
use App\Models\Realization;
use App\Models\RealizationDetail;
use App\Models\VerificationJournalDetail;

class VerificationJournalDetailDescriptionEnricher
{
    public static function enrich(VerificationJournalDetail $detail): VerificationJournalDetail
    {
        if (! $detail->realization_no || $detail->debit_credit !== 'debit') {
            return $detail;
        }

        $realization = Realization::where('nomor', $detail->realization_no)->first();

        if (! $realization) {
            return $detail;
        }

        $matchedDetail = self::matchRealizationDetail($detail, $realization);

        if (! $matchedDetail) {
            return $detail;
        }

        $additionalInfo = self::buildAdditionalInfo($matchedDetail);

        if ($additionalInfo !== []) {
            $detail->description = $detail->description."\n[".implode(' | ', $additionalInfo).']';
        }

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

    private static function matchRealizationDetail(
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
