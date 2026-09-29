<?php

namespace App\Services;

use App\Models\Faktur;
use Carbon\Carbon;

class PpnReconciliationService
{
    public function __construct(
        private SapService $sapService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function reconcile(string $masaPajak): array
    {
        [$dateFrom, $dateTo] = $this->dateRangeForMasaPajak($masaPajak);

        $purchaseInvoices = $this->sapService->fetchPurchaseInvoicesForDocDateRange($dateFrom, $dateTo);
        $salesInvoices = $this->sapService->fetchArInvoicesForDocDateRange($dateFrom, $dateTo);

        $sapPk = $this->sumVatSum($salesInvoices);
        $sapPm = $this->sumVatSum($purchaseInvoices);

        $appPk = (float) Faktur::query()
            ->where('type', 'sales')
            ->where('masa_pajak', $masaPajak)
            ->sum('ppn');

        $appPm = (float) Faktur::query()
            ->where('type', 'purchase')
            ->where('masa_pajak', $masaPajak)
            ->sum('ppn');

        $exposure = $this->buildMissingFakturExposure($purchaseInvoices);

        $diffPk = round($sapPk - $appPk, 2);
        $diffPm = round($sapPm - $appPm, 2);
        $diffSapApp = round($diffPk + $diffPm, 2);
        $kbLb = round($sapPk - $sapPm, 2);
        $diffPkPm = round($appPk - $appPm, 2);

        return [
            'masa_pajak' => $masaPajak,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'sap' => [
                'pk_total' => $sapPk,
                'pm_total' => $sapPm,
                'purchase_invoice_count' => count($purchaseInvoices),
                'sales_invoice_count' => count($salesInvoices),
            ],
            'app' => [
                'pk_total' => $appPk,
                'pm_total' => $appPm,
            ],
            'totals' => [
                'pk_total' => $sapPk,
                'pm_total' => $sapPm,
                'kb_lb' => $kbLb,
                'diff_sap_app' => $diffSapApp,
                'diff_coretax_app' => null,
                'diff_pk_pm' => $diffPkPm,
            ],
            'diff_detail' => [
                'pk_sap_minus_app' => $diffPk,
                'pm_sap_minus_app' => $diffPm,
            ],
            'missing_faktur_exposure' => $exposure,
            'reconciled_at' => now()->toIso8601String(),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $purchaseInvoices
     * @return list<array<string, mixed>>
     */
    public function buildMissingFakturExposure(array $purchaseInvoices): array
    {
        $rows = [];

        foreach ($purchaseInvoices as $invoice) {
            $vatSum = (float) ($invoice['VatSum'] ?? 0);
            if ($vatSum <= 0) {
                continue;
            }

            $fpNum = trim((string) ($invoice['U_MIS_FPNum'] ?? ''));
            if ($fpNum !== '') {
                continue;
            }

            $docDate = isset($invoice['DocDate']) ? substr((string) $invoice['DocDate'], 0, 10) : null;

            $rows[] = [
                'doc_num' => (string) ($invoice['DocNum'] ?? ''),
                'doc_date' => $docDate,
                'card_code' => (string) ($invoice['CardCode'] ?? ''),
                'card_name' => (string) ($invoice['CardName'] ?? ''),
                'vat_sum' => round($vatSum, 2),
                'doc_total' => round((float) ($invoice['DocTotal'] ?? 0), 2),
            ];
        }

        usort($rows, static fn (array $a, array $b): int => strcmp($a['card_code'], $b['card_code']));

        return $rows;
    }

    /**
     * @param  list<array<string, mixed>>  $documents
     */
    private function sumVatSum(array $documents): float
    {
        $sum = 0.0;

        foreach ($documents as $document) {
            $sum += (float) ($document['VatSum'] ?? 0);
        }

        return round($sum, 2);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function dateRangeForMasaPajak(string $masaPajak): array
    {
        if (preg_match('/^\d{4}-\d{2}$/', $masaPajak) !== 1) {
            throw new \InvalidArgumentException('masa_pajak harus format YYYY-MM.');
        }

        $start = Carbon::createFromFormat('Y-m', $masaPajak)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        return [$start->format('Y-m-d'), $end->format('Y-m-d')];
    }
}
