<?php

namespace App\Services;

use App\Models\BpjsApInvoice;
use Illuminate\Support\Facades\Log;
use Throwable;

class BpjsApInvoiceSapStatusService
{
    /**
     * @return array{DocumentStatus?: string, Cancelled?: string, DocEntry?: int, DocNum?: int|string, DocTotal?: float, PaidToDate?: float, NumAtCard?: string}|null
     */
    public function refresh(BpjsApInvoice $invoice, ?SapService $sap = null): ?array
    {
        if (empty($invoice->sap_doc_entry)) {
            return null;
        }

        $sap ??= app(SapService::class);

        try {
            $status = $sap->getPurchaseInvoiceStatus($invoice->sap_doc_entry);

            if ($status === null) {
                return null;
            }

            $invoice->update([
                'sap_document_status' => $status['DocumentStatus'] ?? null,
                'sap_cancelled' => $this->parseSapCancelled($status['Cancelled'] ?? null),
                'sap_status_synced_at' => now(),
            ]);

            return $status;
        } catch (Throwable $exception) {
            Log::warning('BPJS AP Invoice SAP status refresh failed', [
                'bpjs_ap_invoice_id' => $invoice->id,
                'sap_doc_entry' => $invoice->sap_doc_entry,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @param  list<int>|null  $ids
     * @return array{checked: int, updated: int, failed: int}
     */
    public function refreshAll(?array $ids = null): array
    {
        $query = BpjsApInvoice::query()
            ->whereNotNull('sap_doc_entry')
            ->where('sap_doc_entry', '!=', '');

        if ($ids !== null && $ids !== []) {
            $query->whereIn('id', $ids);
        }

        $checked = 0;
        $updated = 0;
        $failed = 0;

        foreach ($query->cursor() as $invoice) {
            $checked++;
            $result = $this->refresh($invoice);

            if ($result !== null) {
                $updated++;
            } else {
                $failed++;
            }
        }

        return [
            'checked' => $checked,
            'updated' => $updated,
            'failed' => $failed,
        ];
    }

    /**
     * @return array{checked: int, updated: int, failed: int}
     */
    public function refreshStale(int $limit = 15, int $maxAgeMinutes = 10): array
    {
        $threshold = now()->subMinutes($maxAgeMinutes);

        $invoices = BpjsApInvoice::query()
            ->whereNotNull('sap_doc_entry')
            ->where('sap_doc_entry', '!=', '')
            ->where(function ($query) use ($threshold) {
                $query->whereNull('sap_status_synced_at')
                    ->orWhere('sap_status_synced_at', '<', $threshold);
            })
            ->orderBy('sap_status_synced_at')
            ->orderBy('id')
            ->limit($limit)
            ->get();

        $checked = $invoices->count();
        $updated = 0;
        $failed = 0;

        foreach ($invoices as $invoice) {
            $result = $this->refresh($invoice);

            if ($result !== null) {
                $updated++;
            } else {
                $failed++;
            }
        }

        return [
            'checked' => $checked,
            'updated' => $updated,
            'failed' => $failed,
        ];
    }

    private function parseSapCancelled(mixed $cancelled): ?bool
    {
        if ($cancelled === null || $cancelled === '') {
            return null;
        }

        $value = strtoupper(trim((string) $cancelled));

        if (in_array($value, ['TYES', 'Y'], true)) {
            return true;
        }

        if (in_array($value, ['TNO', 'N'], true)) {
            return false;
        }

        return null;
    }
}
