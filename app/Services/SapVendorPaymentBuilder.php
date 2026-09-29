<?php

namespace App\Services;

use App\Models\Account;
use App\Models\SapBusinessPartner;
use Carbon\Carbon;

class SapVendorPaymentBuilder
{
    public const MEANS_CASH = 'cash';

    public const MEANS_TRANSFER = 'transfer';

    public const MEANS_CREDIT_MEMO = 'credit_memo';

    public const AMOUNT_TOLERANCE = 0.5;

    /**
     * @param  array{invoice_number?: string, amount?: float|int|string, payment_date?: string|null, remarks?: string|null}  $invoice
     * @param  array{DocEntry?: int, DocNum?: int|string, CardCode?: string, DocumentStatus?: string, Cancelled?: string, DocTotal?: float|int|string, PaidToDate?: float|int|string}  $apInvoice
     */
    /**
     * @var array{total: float, entries: list<array{WTCode: string, WTAmount: float}>}
     */
    protected array $withholding;

    protected bool $remarksFromJournalRemarks = false;

    public function __construct(
        protected array $invoice,
        protected array $apInvoice,
        protected SapBusinessPartner $partner,
        protected ?Account $account = null,
        protected string $paymentMeans = self::MEANS_TRANSFER,
        protected ?string $paymentDate = null,
        protected ?float $paymentAmount = null,
        protected ?string $preparedBy = null,
        protected ?string $approvedBy = null,
        /**
         * @var array{DocEntry?: int, DocNum?: int|string, CardCode?: string, DocumentStatus?: string, Cancelled?: string, DocTotal?: float|int|string, PaidToDate?: float|int|string}|null
         */
        protected ?array $creditMemoDocument = null,
    ) {
        $this->withholding = self::openWithholdingTax($apInvoice);
    }

    public function withRemarksFromJournalRemarks(bool $enabled = true): static
    {
        $this->remarksFromJournalRemarks = $enabled;

        return $this;
    }

    /**
     * @param  array{WithholdingTaxDataCollection?: list<array<string, mixed>>}  $apInvoice
     * @return array{total: float, entries: list<array{WTCode: string, WTAmount: float}>}
     */
    public static function openWithholdingTax(array $apInvoice): array
    {
        $collection = $apInvoice['WithholdingTaxDataCollection'] ?? [];

        if (! is_array($collection)) {
            return ['total' => 0.0, 'entries' => []];
        }

        $entries = [];
        $total = 0.0;

        foreach ($collection as $item) {
            if (! is_array($item)) {
                continue;
            }

            $status = $item['Status'] ?? 'bost_Open';
            if ($status !== 'bost_Open') {
                continue;
            }

            $wtAmount = (float) ($item['WTAmount'] ?? 0);
            if ($wtAmount <= 0) {
                continue;
            }

            $entries[] = [
                'WTCode' => (string) ($item['WTCode'] ?? ''),
                'WTAmount' => $wtAmount,
            ];
            $total += $wtAmount;
        }

        return ['total' => $total, 'entries' => $entries];
    }

    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        $amount = $this->paymentAmountValue();
        $paymentDate = $this->resolvePaymentDate()->format('Y-m-d');
        $cashAccount = (string) ($this->account?->sap_account ?? '');

        $journalRemarks = $this->buildJournalRemarks();

        $withholdingTotal = $this->withholdingTotal();
        // SAP mengaplikasikan PPh23 otomatis dari WTax terbuka di AP invoice; Payment entity
        // tidak mengenal WithholdingTaxDataCollection (hanya WithholdingTaxDataWTXCollection) -> jangan kirim koleksi WTax.
        $sumApplied = $withholdingTotal > 0 ? $amount + $withholdingTotal : $amount;

        if ($this->paymentMeans === self::MEANS_CREDIT_MEMO) {
            $creditApplied = $this->creditMemoAppliedAmount();
            $payment = [
                'CardCode' => $this->partner->code,
                'DocDate' => $paymentDate,
                'DocType' => 'rSupplier',
                'PaymentInvoices' => [
                    [
                        'DocEntry' => (int) ($this->creditMemoDocument['DocEntry'] ?? 0),
                        'InvoiceType' => 'it_PurchaseCreditNote',
                        'SumApplied' => $creditApplied,
                    ],
                    [
                        'DocEntry' => (int) ($this->apInvoice['DocEntry'] ?? 0),
                        'InvoiceType' => 'it_PurchaseInvoice',
                        'SumApplied' => $creditApplied,
                    ],
                ],
                'JournalRemarks' => $journalRemarks,
                'U_MIS_Signature1' => $this->trimmedSignature($this->preparedBy),
                'U_MIS_Signature2' => $this->trimmedSignature($this->approvedBy),
            ];
        } else {
            $paymentInvoice = [
                'DocEntry' => (int) ($this->apInvoice['DocEntry'] ?? 0),
                'InvoiceType' => 'it_PurchaseInvoice',
                'SumApplied' => $sumApplied,
            ];

            $payment = [
                'CardCode' => $this->partner->code,
                'DocDate' => $paymentDate,
                'DocType' => 'rSupplier',
                'PaymentInvoices' => [$paymentInvoice],
                'JournalRemarks' => $journalRemarks,
                'U_MIS_Signature1' => $this->trimmedSignature($this->preparedBy),
                'U_MIS_Signature2' => $this->trimmedSignature($this->approvedBy),
            ];

            if ($this->paymentMeans === self::MEANS_CASH) {
                $payment['CashAccount'] = $cashAccount;
                $payment['CashSum'] = $amount;
            } else {
                $payment['TransferAccount'] = $cashAccount;
                $payment['TransferSum'] = $amount;
                $payment['TransferDate'] = $paymentDate;
            }
        }

        // SAP B1: OVPM.Comments dipetakan ke properti SL "Remarks" pada entity VendorPayments; "Comments" tidak ada di entity ini (pernah ditolak SAP, lihat commit 27b451d).
        if ($this->remarksFromJournalRemarks) {
            $payment['Remarks'] = $journalRemarks;
        }

        return $payment;
    }

    /**
     * @return list<string>
     */
    public function validate(bool $requirePaymentAccount = false): array
    {
        $errors = [];

        if (! $this->partner->active) {
            $errors[] = "SAP Business Partner '{$this->partner->code}' ({$this->partner->name}) is not active.";
        }

        if (! in_array($this->partner->type, ['S', SapBusinessPartner::TYPE_SUPPLIER], true)) {
            $errors[] = "SAP Business Partner '{$this->partner->code}' must be a Supplier/Vendor (current type: {$this->partner->type}).";
        }

        if (empty($this->partner->code)) {
            $errors[] = 'SAP Business Partner does not have a CardCode.';
        }

        $invoiceNumber = trim((string) ($this->invoice['invoice_number'] ?? ''));
        if ($invoiceNumber === '') {
            $errors[] = 'Invoice number is required to resolve the SAP AP Invoice.';
        }

        if ($this->paymentAmountValue() <= 0) {
            $errors[] = 'Payment amount must be greater than zero.';
        }

        $errors = array_merge($errors, $this->validateApInvoice());

        $remaining = $this->remainingBalance();
        $withholdingTotal = $this->withholdingTotal();

        if ($withholdingTotal > 0 && $remaining !== null) {
            $netAmount = $this->paymentAmountValue();
            $grossApplied = $netAmount + $withholdingTotal;

            if (abs($grossApplied - $remaining) > self::AMOUNT_TOLERANCE) {
                $errors[] = 'When PPh23 withholding applies, payment must settle the full invoice balance (net cash + PPh23 = remaining). '
                    .'Net payment: Rp '.number_format($netAmount, 0, ',', '.')
                    .', PPh23: Rp '.number_format($withholdingTotal, 0, ',', '.')
                    .', invoice remaining: Rp '.number_format($remaining, 0, ',', '.').'.';
            }
        } elseif ($remaining !== null && $this->paymentAmountValue() > $remaining + self::AMOUNT_TOLERANCE) {
            $errors[] = 'Payment amount exceeds the remaining SAP balance of Rp '.number_format($remaining, 0, ',', '.').'.';
        }

        if ($this->paymentMeans === self::MEANS_CREDIT_MEMO) {
            $errors = array_merge($errors, $this->validateCreditMemoPayment());
        }

        if ($requirePaymentAccount) {
            if (! in_array($this->paymentMeans, [self::MEANS_CASH, self::MEANS_TRANSFER, self::MEANS_CREDIT_MEMO], true)) {
                $errors[] = 'Payment means must be cash, transfer, or credit memo.';
            }

            if ($this->trimmedSignature($this->preparedBy) === '') {
                $errors[] = 'Prepared by is required.';
            }

            if ($this->trimmedSignature($this->approvedBy) === '') {
                $errors[] = 'Approved by is required.';
            }

            if ($this->paymentMeans !== self::MEANS_CREDIT_MEMO) {
                if (! $this->account) {
                    $errors[] = 'A cash/bank account must be selected.';
                } elseif (empty($this->account->sap_account)) {
                    $errors[] = "Account '{$this->account->account_name}' does not have a SAP account mapping (sap_account).";
                }
            }
        }

        return array_values(array_unique($errors));
    }

    /**
     * @return array<string, mixed>
     */
    public function getPreviewData(): array
    {
        $amount = $this->paymentAmountValue();
        $apTotal = isset($this->apInvoice['DocTotal']) ? (float) $this->apInvoice['DocTotal'] : null;
        $paidToDate = $this->paidToDate();
        $remaining = $this->remainingBalance();
        $amountMismatch = $apTotal !== null && abs($apTotal - $this->invoiceAmount()) > self::AMOUNT_TOLERANCE;
        $withholdingTotal = $this->withholdingTotal();
        $grossApplied = $amount + $withholdingTotal;
        $journalRemarks = $this->buildJournalRemarks();

        if ($withholdingTotal > 0 && $remaining !== null) {
            $isPartial = $grossApplied < $remaining - self::AMOUNT_TOLERANCE;
            $fullyPaidAfter = $grossApplied >= $remaining - self::AMOUNT_TOLERANCE;
        } else {
            $isPartial = $remaining !== null && $amount < $remaining - self::AMOUNT_TOLERANCE;
            $fullyPaidAfter = $remaining !== null && $amount >= $remaining - self::AMOUNT_TOLERANCE;
        }

        return [
            'invoice' => [
                'invoice_number' => $this->invoice['invoice_number'] ?? null,
                'amount' => $this->invoiceAmount(),
                'payment_date' => $this->resolvePaymentDate()->format('Y-m-d'),
                'remarks' => $this->invoice['remarks'] ?? null,
            ],
            'partner' => [
                'code' => $this->partner->code,
                'name' => $this->partner->name,
            ],
            'ap_invoice' => [
                'doc_entry' => $this->apInvoice['DocEntry'] ?? null,
                'doc_num' => $this->apInvoice['DocNum'] ?? null,
                'doc_total' => $apTotal,
                'paid_to_date' => $paidToDate,
                'remaining_balance' => $remaining,
                'card_code' => $this->apInvoice['CardCode'] ?? null,
                'status' => $this->apInvoice['DocumentStatus'] ?? null,
            ],
            'payment_amount' => $amount,
            'net_amount' => $amount,
            'withholding' => $this->withholding,
            'gross_applied' => $grossApplied,
            'is_partial' => $isPartial,
            'fully_paid_after' => $fullyPaidAfter,
            'amount_mismatch' => $amountMismatch,
            'payment_means' => $this->paymentMeans,
            'prepared_by' => $this->trimmedSignature($this->preparedBy),
            'approved_by' => $this->trimmedSignature($this->approvedBy),
            'account' => $this->account ? [
                'id' => $this->account->id,
                'account_number' => $this->account->account_number,
                'account_name' => $this->account->account_name,
                'sap_account' => $this->account->sap_account,
            ] : null,
            'journal_remarks' => $journalRemarks,
            'remarks' => $this->remarksFromJournalRemarks ? $journalRemarks : null,
            'credit_memo' => $this->paymentMeans === self::MEANS_CREDIT_MEMO ? [
                'doc_entry' => $this->creditMemoDocument['DocEntry'] ?? null,
                'doc_num' => $this->creditMemoDocument['DocNum'] ?? null,
                'remaining_balance' => $this->creditMemoRemainingBalance(),
                'applied_amount' => $this->creditMemoAppliedAmount(),
            ] : null,
        ];
    }

    public function creditMemoAppliedAmount(): float
    {
        if ($this->paymentMeans !== self::MEANS_CREDIT_MEMO) {
            return 0.0;
        }

        $invoiceNet = $this->paymentAmountValue();
        $cmRemaining = $this->creditMemoRemainingBalance();

        return min($invoiceNet, $cmRemaining);
    }

    public function creditMemoRemainingBalance(): ?float
    {
        if ($this->creditMemoDocument === null || ! isset($this->creditMemoDocument['DocTotal'])) {
            return null;
        }

        return max(0.0, (float) $this->creditMemoDocument['DocTotal'] - (float) ($this->creditMemoDocument['PaidToDate'] ?? 0));
    }

    public function withholdingTotal(): float
    {
        return $this->withholding['total'];
    }

    public function remainingBalance(): ?float
    {
        if (! isset($this->apInvoice['DocTotal'])) {
            return null;
        }

        return max(0.0, (float) $this->apInvoice['DocTotal'] - $this->paidToDate());
    }

    public function paidToDate(): float
    {
        return (float) ($this->apInvoice['PaidToDate'] ?? 0);
    }

    /**
     * @return list<string>
     */
    protected function validateApInvoice(): array
    {
        $errors = [];
        $expectedCardCode = (string) $this->partner->code;
        $cardCode = $this->apInvoice['CardCode'] ?? null;

        if (empty($this->apInvoice['DocEntry'])) {
            $errors[] = 'Linked SAP AP Invoice could not be found. The AP Invoice must already exist in SAP B1.';

            return $errors;
        }

        if ($cardCode && $expectedCardCode !== '' && $cardCode !== $expectedCardCode) {
            $errors[] = "SAP AP Invoice belongs to vendor {$cardCode}, expected {$expectedCardCode}.";
        }

        if (strtoupper((string) ($this->apInvoice['Cancelled'] ?? 'N')) === 'Y') {
            $errors[] = 'Linked SAP AP Invoice is cancelled and cannot be paid.';
        }

        $status = $this->apInvoice['DocumentStatus'] ?? null;
        if ($status && $status !== 'bost_Open') {
            $errors[] = "Linked SAP AP Invoice is not open for payment (status: {$status}).";
        }

        $remaining = $this->remainingBalance();
        if ($remaining !== null && $remaining <= self::AMOUNT_TOLERANCE) {
            $errors[] = 'Linked SAP AP Invoice is already fully paid in SAP B1.';
        }

        return $errors;
    }

    /**
     * @return list<string>
     */
    public function paymentIncludesCashOrTransferComponent(): bool
    {
        if (in_array($this->paymentMeans, [self::MEANS_CASH, self::MEANS_TRANSFER], true)) {
            return $this->paymentAmountValue() > self::AMOUNT_TOLERANCE;
        }

        if ($this->paymentMeans === self::MEANS_CREDIT_MEMO) {
            return false;
        }

        return false;
    }

    protected function validateCreditMemoPayment(): array
    {
        $errors = [];

        if (! $this->paymentIncludesCashOrTransferComponent()) {
            $errors[] = 'Pembayaran dengan credit memo tidak bisa berdiri sendiri: SAP mensyaratkan ada baris kas/transfer dalam dokumen pembayaran yang sama. Silakan selesaikan penerapan credit memo melalui SAP, atau gunakan pembayaran transfer.';
        }

        if ($this->withholdingTotal() > 0) {
            $errors[] = 'PPh23 withholding cannot be settled with AP Credit Memo in this flow. Use transfer or cash payment.';
        }

        if (empty($this->creditMemoDocument['DocEntry'])) {
            $errors[] = 'SAP Purchase Credit Note must be selected.';
        }

        $expectedCardCode = (string) $this->partner->code;
        $cmCardCode = (string) ($this->creditMemoDocument['CardCode'] ?? '');
        if ($cmCardCode !== '' && $expectedCardCode !== '' && $cmCardCode !== $expectedCardCode) {
            $errors[] = "Credit note belongs to vendor {$cmCardCode}, expected {$expectedCardCode}.";
        }

        if (strtoupper((string) ($this->creditMemoDocument['Cancelled'] ?? 'N')) === 'Y') {
            $errors[] = 'Selected SAP Purchase Credit Note is cancelled.';
        }

        $cmStatus = $this->creditMemoDocument['DocumentStatus'] ?? null;
        if ($cmStatus && $cmStatus !== 'bost_Open') {
            $errors[] = "Selected SAP Purchase Credit Note is not open (status: {$cmStatus}).";
        }

        $cmRemaining = $this->creditMemoRemainingBalance();
        if ($cmRemaining !== null && $cmRemaining <= self::AMOUNT_TOLERANCE) {
            $errors[] = 'Selected SAP Purchase Credit Note has no remaining balance.';
        }

        $applied = $this->creditMemoAppliedAmount();
        if ($applied <= self::AMOUNT_TOLERANCE) {
            $errors[] = 'Credit note applied amount must be greater than zero.';
        }

        if ($cmRemaining !== null && $applied > $cmRemaining + self::AMOUNT_TOLERANCE) {
            $errors[] = 'Applied amount exceeds the remaining credit note balance of Rp '.number_format($cmRemaining, 0, ',', '.').'.';
        }

        return $errors;
    }

    protected function invoiceAmount(): float
    {
        return (float) ($this->invoice['amount'] ?? 0);
    }

    protected function paymentAmountValue(): float
    {
        if ($this->paymentAmount !== null) {
            return $this->paymentAmount;
        }

        $remaining = $this->remainingBalance();
        if ($remaining !== null && $remaining > 0) {
            return $remaining;
        }

        return $this->invoiceAmount();
    }

    protected function resolvePaymentDate(): Carbon
    {
        $date = $this->paymentDate ?: ($this->invoice['payment_date'] ?? null);

        return $date ? Carbon::parse($date) : Carbon::today();
    }

    protected function buildJournalRemarks(): string
    {
        $invoiceNumber = (string) ($this->invoice['invoice_number'] ?? '');

        return mb_substr(trim('Payment for Invoice '.$invoiceNumber), 0, 254);
    }

    protected function trimmedSignature(?string $value): string
    {
        return mb_substr(trim((string) $value), 0, 100);
    }
}
