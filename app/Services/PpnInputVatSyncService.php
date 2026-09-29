<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Faktur;
use App\Models\PpnInputSync;
use App\Models\PpnSyncRun;
use App\Support\Sap\AoPpnin1Query;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class PpnInputVatSyncService
{
    public function __construct(
        protected SapService $sapService,
        protected FakturPpnCalculationService $ppnCalculation
    ) {}

    /**
     * @return array{startDate: string, endDate: string}
     */
    public function dateRangeForLookback(int $lookbackDays): array
    {
        $end = Carbon::today();
        $start = Carbon::today()->subDays(max(1, $lookbackDays));

        return [
            'startDate' => $start->format('Y-m-d'),
            'endDate' => $end->format('Y-m-d'),
        ];
    }

    public function sync(int $lookbackDays = 60, ?int $triggeredByUserId = null): PpnSyncRun
    {
        $run = PpnSyncRun::query()->create([
            'started_at' => now(),
            'status' => 'running',
            'triggered_by' => $triggeredByUserId,
        ]);

        try {
            $range = $this->dateRangeForLookback($lookbackDays);
            $rawRows = $this->fetchFromSap($range['startDate'], $range['endDate']);
            $batch = 'ppn-'.now()->format('YmdHis');

            $upserted = 0;
            $syncRows = [];

            foreach ($rawRows as $raw) {
                $attrs = $this->mapSapRowToAttributes($raw, $batch);
                if ($attrs === null) {
                    continue;
                }

                PpnInputSync::query()->updateOrCreate(
                    [
                        'trans_id' => $attrs['trans_id'],
                        'line_id' => $attrs['line_id'],
                    ],
                    $attrs
                );
                $upserted++;
                $syncRows[] = $attrs;
            }

            $fakturCreated = $this->dispatchNewRowsToFakturs($syncRows, $triggeredByUserId);

            $run->update([
                'finished_at' => now(),
                'status' => 'success',
                'rows_fetched' => count($rawRows),
                'rows_upserted' => $upserted,
                'rows_faktur_created' => $fakturCreated,
                'message' => sprintf(
                    'Rentang %s s/d %s: %d baris dari SAP, %d upsert, %d faktur baru.',
                    $range['startDate'],
                    $range['endDate'],
                    count($rawRows),
                    $upserted,
                    $fakturCreated
                ),
            ]);
        } catch (Throwable $exception) {
            Log::error('PPN input VAT sync failed', [
                'run_id' => $run->id,
                'error' => $exception->getMessage(),
            ]);

            $run->update([
                'finished_at' => now(),
                'status' => 'failed',
                'message' => $exception->getMessage(),
            ]);
        }

        return $run->fresh();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetchFromSap(string $startDate, string $endDate): array
    {
        return $this->sapService->fetchPpnInputVatLines($startDate, $endDate);
    }

    /**
     * @param  array<string, mixed>  $raw
     * @return array<string, mixed>|null
     */
    public function mapSapRowToAttributes(array $raw, string $batch): ?array
    {
        $transId = (int) ($raw['trans_id'] ?? $raw['TransId'] ?? 0);
        $lineId = (int) ($raw['line_id'] ?? $raw['Line_ID'] ?? 0);

        if ($transId <= 0 || $lineId < 0) {
            return null;
        }

        $amount = (float) ($raw['amount'] ?? $raw['Amount'] ?? 0);
        if ($amount <= 0) {
            return null;
        }

        return [
            'trans_id' => $transId,
            'line_id' => $lineId,
            'document_no' => $this->stringOrNull($raw['document_no'] ?? $raw['DocumentNo'] ?? null),
            'doc_type' => $this->resolveDocType($raw),
            'creation_date' => $this->normalizeDate($raw['creation_date'] ?? $raw['CreationDate'] ?? null),
            'posting_date' => $this->normalizeDate($raw['posting_date'] ?? $raw['PostingDate'] ?? null),
            'faktur_date' => $this->normalizeDate($raw['faktur_date'] ?? $raw['FakturDate'] ?? null),
            'vendor_code' => $this->stringOrNull($raw['vendor_code'] ?? $raw['VendorCode'] ?? null),
            'vendor_name' => $this->stringOrNull($raw['vendor_name'] ?? $raw['VendorName'] ?? null),
            'faktur_no' => $this->stringOrNull($raw['faktur_no'] ?? $raw['FakturNo'] ?? null),
            'amount' => round($amount, 2),
            'project_code' => $this->stringOrNull($raw['project_code'] ?? $raw['ProjectCode'] ?? null),
            'remark' => $this->stringOrNull($raw['remark'] ?? $raw['Remark'] ?? null),
            'sap_user' => $this->stringOrNull($raw['sap_user'] ?? $raw['SapUser'] ?? null),
            'invoice_no' => $this->stringOrNull($raw['invoice_no'] ?? $raw['InvoiceNo'] ?? null),
            'invoice_remarks' => $this->stringOrNull($raw['invoice_remarks'] ?? $raw['InvoiceRemarks'] ?? null),
            'sync_batch' => $batch,
            'synced_at' => now(),
            'source' => 'sap_auto',
        ];
    }

    /**
     * Kode TransType SAP → nama jenis dokumen.
     * Dipetakan di aplikasi (bukan di SQL) karena parser SQLQueries SAP menolak ekspresi CASE.
     */
    private const TRANS_TYPES = [
        '-2' => 'Opening Balance',
        '13' => 'AR Invoice',
        '14' => 'AR Credit Memo',
        '203' => 'AR DP',
        '15' => 'Material Issue',
        '16' => 'Material Return',
        '18' => 'AP Invoice',
        '19' => 'AP Credit Memo',
        '204' => 'AP DP',
        '20' => 'Goods Receipt PO',
        '202' => 'Production Order',
        '21' => 'Goods Return',
        '24' => 'Incoming Payments',
        '30' => 'Journal Entry',
        '46' => 'Outgoing Payments',
        '59' => 'Goods Receipt',
        '60' => 'Goods Issue',
        '67' => 'InventoryTransfer',
        '69' => 'Landed Costs',
        '321' => 'Intenal Reconciliation',
        '162' => 'Inventory Revaluation',
    ];

    /**
     * @param  array<string, mixed>  $raw
     */
    private function resolveDocType(array $raw): ?string
    {
        $explicit = $this->stringOrNull($raw['doc_type'] ?? $raw['DocType'] ?? null);

        if ($explicit !== null && $explicit !== '') {
            return $explicit;
        }

        $code = $raw['trans_type'] ?? $raw['TransType'] ?? null;

        if ($code === null || $code === '') {
            return null;
        }

        return self::TRANS_TYPES[(string) $code] ?? null;
    }

    /**
     * @param  list<array<string, mixed>>  $syncRowAttributes
     */
    public function dispatchNewRowsToFakturs(array $syncRowAttributes, ?int $createdByUserId = null): int
    {
        if ($syncRowAttributes === []) {
            return 0;
        }

        $creatorId = $createdByUserId ?? \App\Models\User::query()->orderBy('id')->value('id');
        if ($creatorId === null) {
            throw new \RuntimeException('Cannot create faktur from PPN sync: no user available for created_by.');
        }

        $created = 0;
        $batchNo = (int) Faktur::query()->max('batch_no') + 1;

        DB::transaction(function () use ($syncRowAttributes, &$created, $batchNo, $creatorId) {
            foreach ($syncRowAttributes as $attrs) {
                $exists = Faktur::query()
                    ->where('sap_trans_id', $attrs['trans_id'])
                    ->where('sap_line_id', $attrs['line_id'])
                    ->exists();

                if ($exists) {
                    continue;
                }

                $vendorCode = $attrs['vendor_code'] ?? 'UNKNOWN';
                $vendor = Customer::query()->firstOrCreate(
                    ['code' => $vendorCode],
                    [
                        'name' => $attrs['vendor_name'] ?? $vendorCode,
                        'type' => 'vendor',
                    ]
                );

                $ppnAmount = (float) $attrs['amount'];
                $remarksText = trim(implode(' ', array_filter([
                    $attrs['remark'] ?? '',
                    $attrs['invoice_remarks'] ?? '',
                ])));

                $calculation = $this->ppnCalculation->calculateFromPpnAmount(
                    $ppnAmount,
                    null,
                    $remarksText !== '' ? $remarksText : null
                );

                $remarks = $remarksText;
                if ($calculation['review_note'] !== null) {
                    $suffix = '[PPN monitoring] '.$calculation['review_note'];
                    $remarks = trim(($remarks !== '' ? $remarks.' ' : '').$suffix);
                }

                if (($attrs['faktur_date'] ?? null) === null) {
                    $fpNote = '[PPN sync] Tanggal faktur pajak kosong; periode memakai RefDate/posting.';
                    $remarks = trim(($remarks !== '' ? $remarks.' ' : '').$fpNote);
                }

                Faktur::query()->create([
                    'customer_id' => $vendor->id,
                    'create_date' => $attrs['creation_date'] ?? $attrs['posting_date'],
                    'posting_date' => $attrs['posting_date'],
                    'invoice_date' => $attrs['posting_date'] ?? $attrs['creation_date'] ?? now()->toDateString(),
                    'doc_num' => $attrs['document_no'],
                    'invoice_no' => $attrs['invoice_no'],
                    'type' => 'purchase',
                    'account' => '11603001',
                    'faktur_no' => $attrs['faktur_no'],
                    'faktur_date' => $attrs['faktur_date'],
                    'masa_pajak' => $this->ppnCalculation->masaPajakFromDate($attrs['faktur_date'] ?? null),
                    'dpp' => $calculation['dpp'],
                    'ppn' => $ppnAmount,
                    'ppn_rate' => $calculation['ppn_rate'],
                    'dpp_calculated' => $calculation['dpp_calculated'],
                    'dpp_source' => $calculation['dpp_source'],
                    'validation_status' => $calculation['validation_status'],
                    'remarks' => $remarks !== '' ? $remarks : null,
                    'user_code' => $attrs['sap_user'],
                    'batch_no' => $batchNo,
                    'sync_source' => 'sap_auto',
                    'sap_trans_id' => $attrs['trans_id'],
                    'sap_line_id' => $attrs['line_id'],
                    'created_by' => $creatorId,
                ]);

                $created++;
            }
        });

        return $created;
    }

    public function ensureSapQueryRegistered(): void
    {
        $this->sapService->ensurePpnInputVatSqlQuery();
    }

    public static function aoPpnin1SqlText(): string
    {
        return AoPpnin1Query::sqlText();
    }

    public static function aoPpnin1SqlCode(): string
    {
        return AoPpnin1Query::CODE;
    }

    private function normalizeDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = trim((string) $value);

        if (preg_match('/^\d{8}$/', $value) === 1) {
            return substr($value, 0, 4).'-'.substr($value, 4, 2).'-'.substr($value, 6, 2);
        }

        return substr($value, 0, 10);
    }

    private function stringOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }
}
