<?php

namespace App\Services;

use App\Models\CoretaxInputVat;
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

        $coretaxRows = CoretaxInputVat::query()
            ->where('masa_pajak', $masaPajak)
            ->get();

        $coretaxPm = round((float) $coretaxRows->sum('ppn'), 2);
        $coretaxCount = $coretaxRows->count();
        $hasCoretaxData = $coretaxCount > 0;
        $diffCoretaxApp = $hasCoretaxData ? round($coretaxPm - $appPm, 2) : null;

        $threeWay = $this->buildThreeWayFindings($purchaseInvoices, $masaPajak, $coretaxRows);

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
                'pm_faktur_count' => (int) Faktur::query()
                    ->where('type', 'purchase')
                    ->where('masa_pajak', $masaPajak)
                    ->whereNotNull('faktur_no')
                    ->where('faktur_no', '!=', '')
                    ->count(),
            ],
            'coretax' => [
                'pm_total' => $coretaxPm,
                'faktur_count' => $coretaxCount,
            ],
            'three_way' => $threeWay,
            'totals' => [
                'pk_total' => $sapPk,
                'pm_total' => $sapPm,
                'kb_lb' => $kbLb,
                'diff_sap_app' => $diffSapApp,
                'diff_coretax_app' => $diffCoretaxApp,
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

    /**
     * @param  list<array<string, mixed>>  $purchaseInvoices
     * @param  \Illuminate\Support\Collection<int, CoretaxInputVat>  $coretaxRows
     * @return array<string, mixed>
     */
    public function buildThreeWayFindings(array $purchaseInvoices, string $masaPajak, $coretaxRows): array
    {
        $sapByFp = [];
        foreach ($purchaseInvoices as $invoice) {
            $vatSum = (float) ($invoice['VatSum'] ?? 0);
            if ($vatSum <= 0) {
                continue;
            }
            $fpNum = preg_replace('/\s+/', '', trim((string) ($invoice['U_MIS_FPNum'] ?? ''))) ?? '';
            if ($fpNum === '') {
                continue;
            }
            $sapByFp[$fpNum] = [
                'faktur_no' => $fpNum,
                'doc_num' => (string) ($invoice['DocNum'] ?? ''),
                'vat_sum' => round($vatSum, 2),
                'card_name' => (string) ($invoice['CardName'] ?? ''),
            ];
        }

        $appFakturs = Faktur::query()
            ->with('customer')
            ->where('type', 'purchase')
            ->where('masa_pajak', $masaPajak)
            ->whereNotNull('faktur_no')
            ->where('faktur_no', '!=', '')
            ->get();

        $appByFp = [];
        foreach ($appFakturs as $faktur) {
            $fp = preg_replace('/\s+/', '', (string) $faktur->faktur_no) ?? '';
            if ($fp === '') {
                continue;
            }
            if (! isset($appByFp[$fp])) {
                $appByFp[$fp] = [
                    'faktur_no' => $fp,
                    'ppn' => 0.0,
                    'supplier' => $faktur->customer?->name ?? '',
                ];
            }
            $appByFp[$fp]['ppn'] += (float) $faktur->ppn;
        }

        foreach ($appByFp as $fp => $data) {
            $appByFp[$fp]['ppn'] = round($data['ppn'], 2);
        }

        $coretaxByFp = [];
        foreach ($coretaxRows as $row) {
            $fp = preg_replace('/\s+/', '', (string) $row->faktur_no) ?? '';
            if ($fp === '') {
                continue;
            }
            $coretaxByFp[$fp] = [
                'faktur_no' => $fp,
                'ppn' => round((float) $row->ppn, 2),
                'supplier_name' => $row->supplier_name,
                'status_faktur' => $row->status_faktur,
            ];
        }

        $sapFps = array_keys($sapByFp);
        $appFps = array_keys($appByFp);
        $coretaxFps = array_keys($coretaxByFp);

        $matchedThreeWay = [];
        foreach (array_intersect($sapFps, $appFps, $coretaxFps) as $fp) {
            $matchedThreeWay[] = [
                'faktur_no' => $fp,
                'sap' => $sapByFp[$fp],
                'app' => $appByFp[$fp],
                'coretax' => $coretaxByFp[$fp],
            ];
        }

        $sapAppNotCoretax = [];
        foreach (array_intersect($sapFps, $appFps) as $fp) {
            if (! in_array($fp, $coretaxFps, true)) {
                $sapAppNotCoretax[] = [
                    'faktur_no' => $fp,
                    'sap' => $sapByFp[$fp],
                    'app' => $appByFp[$fp],
                ];
            }
        }

        $coretaxOnly = [];
        $appOrSapFps = array_unique(array_merge($sapFps, $appFps));
        foreach ($coretaxFps as $fp) {
            if (! in_array($fp, $appOrSapFps, true)) {
                $coretaxOnly[] = [
                    'faktur_no' => $fp,
                    'coretax' => $coretaxByFp[$fp],
                ];
            }
        }

        usort($matchedThreeWay, fn (array $a, array $b): int => strcmp($a['faktur_no'], $b['faktur_no']));
        usort($sapAppNotCoretax, fn (array $a, array $b): int => strcmp($a['faktur_no'], $b['faktur_no']));
        usort($coretaxOnly, fn (array $a, array $b): int => strcmp($a['faktur_no'], $b['faktur_no']));

        return [
            'counts' => [
                'sap_pm_with_fp' => count($sapByFp),
                'app_pm_with_fp' => count($appByFp),
                'coretax' => count($coretaxByFp),
                'matched_three_way' => count($matchedThreeWay),
                'sap_app_not_coretax' => count($sapAppNotCoretax),
                'coretax_only' => count($coretaxOnly),
            ],
            'matched_three_way' => $matchedThreeWay,
            'sap_app_not_coretax' => $sapAppNotCoretax,
            'coretax_only' => $coretaxOnly,
        ];
    }
}
