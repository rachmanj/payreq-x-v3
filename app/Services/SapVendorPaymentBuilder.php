<?php

namespace App\Services;

use App\Models\Account;
use App\Models\SapBusinessPartner;
use Carbon\Carbon;

class SapVendorPaymentBuilder
{
    public const MEANS_CASH = 'cash';

    public const MEANS_TRANSFER = 'transfer';

    /**
     * @param  array{invoice_number?: string, amount?: float|int|string, payment_date?: string|null, remarks?: string|null}  $invoice
     * @param  array{DocEntry?: int, DocNum?: int|string, CardCode?: string, DocumentStatus?: string, Cancelled?: string, DocTotal?: float|int|string}  $apInvoice
     */
    public function __construct(
        protected array $invoice,
        protected array $apInvoice,
        protected SapBusinessPartner $partner,
        protected ?Account $account = null,
        protected string $paymentMeans = self::MEANS_TRANSFER,
        protected ?string $paymentDate = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        $amount = $this->amount();
        $paymentDate = $this->resolvePaymentDate()->format('Y-m-d');
        $cashAccount = (string) ($this->account?->sap_account ?? '');

        $payment = [
            'CardCode' => $this->partner->code,
            'DocDate' => $paymentDate,
            'DocType' => 'rSupplier',
            'PaymentInvoices' => [
                [
                    'DocEntry' => (int) ($this->apInvoice['DocEntry'] ?? 0),
                    'InvoiceType' => 'it_PurchaseInvoice',
                    'SumApplied' => $amount,
                ],
            ],
            'JournalRemarks' => $this->buildJournalRemarks(),
        ];

        if ($this->paymentMeans === self::MEANS_CASH) {
            $payment['CashAccount'] = $cashAccount;
            $payment['CashSum'] = $amount;
        } else {
            $payment['TransferAccount'] = $cashAccount;
            $payment['TransferSum'] = $amount;
            $payment['TransferDate'] = $paymentDate;
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

        if ($this->amount() <= 0) {
            $errors[] = 'Invoice amount must be greater than zero.';
        }

        $errors = array_merge($errors, $this->validateApInvoice());

        if ($requirePaymentAccount) {
            if (! in_array($this->paymentMeans, [self::MEANS_CASH, self::MEANS_TRANSFER], true)) {
                $errors[] = 'Payment means must be cash or transfer.';
            }

            if (! $this->account) {
                $errors[] = 'A cash/bank account must be selected.';
            } elseif (empty($this->account->sap_account)) {
                $errors[] = "Account '{$this->account->account_name}' does not have a SAP account mapping (sap_account).";
            }
        }

        return array_values(array_unique($errors));
    }

    /**
     * @return array<string, mixed>
     */
    public function getPreviewData(): array
    {
        $amount = $this->amount();
        $apTotal = isset($this->apInvoice['DocTotal']) ? (float) $this->apInvoice['DocTotal'] : null;
        $amountMismatch = $apTotal !== null && abs($apTotal - $amount) > 0.5;

        return [
            'invoice' => [
                'invoice_number' => $this->invoice['invoice_number'] ?? null,
                'amount' => $amount,
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
                'card_code' => $this->apInvoice['CardCode'] ?? null,
                'status' => $this->apInvoice['DocumentStatus'] ?? null,
            ],
            'amount_mismatch' => $amountMismatch,
            'payment_means' => $this->paymentMeans,
            'account' => $this->account ? [
                'id' => $this->account->id,
                'account_number' => $this->account->account_number,
                'account_name' => $this->account->account_name,
                'sap_account' => $this->account->sap_account,
            ] : null,
        ];
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

        return $errors;
    }

    protected function amount(): float
    {
        return (float) ($this->invoice['amount'] ?? 0);
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
}
