<?php

namespace App\Services;

class VendorCreditMemoAllocationService
{
    /**
     * @param  list<array{doc_entry: int, doc_num?: int|string|null, doc_date?: string|null, remaining_balance: float, num_at_card?: string|null}>  $openInvoices
     * @return list<array{doc_entry: int, doc_num?: int|string|null, amount: float, num_at_card?: string|null}>
     */
    public function allocateOldestFirst(array $openInvoices, float $creditAvailable): array
    {
        if ($creditAvailable <= SapVendorPaymentBuilder::AMOUNT_TOLERANCE) {
            return [];
        }

        $sorted = collect($openInvoices)
            ->filter(fn (array $row) => ($row['remaining_balance'] ?? 0) > SapVendorPaymentBuilder::AMOUNT_TOLERANCE)
            ->sortBy(function (array $row): string {
                $date = (string) ($row['doc_date'] ?? '');
                $entry = str_pad((string) (int) ($row['doc_entry'] ?? 0), 12, '0', STR_PAD_LEFT);

                return $date.'|'.$entry;
            })
            ->values();

        $remainingCredit = $creditAvailable;
        $allocations = [];

        foreach ($sorted as $invoice) {
            if ($remainingCredit <= SapVendorPaymentBuilder::AMOUNT_TOLERANCE) {
                break;
            }

            $invoiceRemaining = (float) $invoice['remaining_balance'];
            $applied = min($remainingCredit, $invoiceRemaining);
            if ($applied <= SapVendorPaymentBuilder::AMOUNT_TOLERANCE) {
                continue;
            }

            $allocations[] = [
                'doc_entry' => (int) $invoice['doc_entry'],
                'doc_num' => $invoice['doc_num'] ?? null,
                'num_at_card' => $invoice['num_at_card'] ?? null,
                'amount' => $applied,
            ];

            $remainingCredit -= $applied;
        }

        return $allocations;
    }

    public function creditMemoRemaining(array $creditMemo): float
    {
        $docTotal = (float) ($creditMemo['DocTotal'] ?? 0);
        $paidToDate = (float) ($creditMemo['PaidToDate'] ?? 0);

        return max(0.0, $docTotal - $paidToDate);
    }
}
