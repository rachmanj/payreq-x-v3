<?php

namespace App\Services;

use App\Models\Installment;
use App\Models\Bilyet;
use Carbon\Carbon;

class SapOutgoingPaymentBuilder
{
    protected Installment $installment;
    protected ?Bilyet $bilyet;
    protected array $config;

    public function __construct(Installment $installment, ?Bilyet $bilyet = null)
    {
        $this->installment = $installment;
        $this->bilyet = $bilyet;
        $this->config = config('services.sap.ap_invoice', []);
    }

    public function build(): array
    {
        $loan = $this->installment->loan;
        $creditor = $loan->creditor;

        $paymentDate = $this->getPaymentDate();
        $cashAccount = $this->getCashAccount();
        $paymentMethod = $this->getPaymentMethod();

        $payment = [
            'CardCode' => $creditor->code ?? $creditor->name,
            'DocDate' => $paymentDate->format('Y-m-d'),
            'DocType' => 'rCustomer', // Outgoing payment
            'PaymentInvoices' => $this->buildPaymentInvoices(),
            'PaymentMethod' => $paymentMethod,
            'CashAccount' => $cashAccount,
            'Comments' => $this->buildComments(),
        ];

        // Add CheckNumber for bilyet payments
        if ($this->bilyet && $this->installment->payment_method === 'bilyet') {
            $checkNumber = $this->bilyet->prefix . $this->bilyet->nomor;
            if (!empty($checkNumber)) {
                $payment['CheckNumber'] = $checkNumber;
            }
        }

        return $payment;
    }

    protected function buildPaymentInvoices(): array
    {
        return [
            [
                'DocEntry' => $this->installment->sap_ap_doc_entry,
                'InvoiceType' => 'it_PurchaseInvoice',
                'SumApplied' => (float) $this->installment->bilyet_amount,
            ]
        ];
    }

    protected function getPaymentDate(): Carbon
    {
        if ($this->installment->payment_method === 'bilyet' && $this->bilyet && $this->bilyet->cair_date) {
            return Carbon::parse($this->bilyet->cair_date);
        }

        if ($this->installment->paid_date) {
            return Carbon::parse($this->installment->paid_date);
        }

        return Carbon::today();
    }

    protected function getCashAccount(): string
    {
        if ($this->installment->payment_method === 'bilyet' && $this->bilyet) {
            $giro = $this->bilyet->giro;
            if ($giro && $giro->sap_account) {
                return $giro->sap_account;
            }
        }

        if ($this->installment->payment_method === 'auto_debit' && $this->installment->account_id) {
            $account = $this->installment->account;
            if ($account && $account->sap_account) {
                return $account->sap_account;
            }
        }

        throw new \Exception('Cash account (sap_account) not found for payment. Please ensure giro or account has SAP account mapping.');
    }

    protected function getPaymentMethod(): string
    {
        if ($this->installment->payment_method === 'bilyet' && $this->bilyet) {
            return $this->mapBilyetTypeToPaymentMethod($this->bilyet->type);
        }

        if ($this->installment->payment_method === 'auto_debit') {
            return 'T'; // Bank Transfer
        }

        return $this->config['default_payment_method'] ?? 'C'; // Default to Check
    }

    protected function mapBilyetTypeToPaymentMethod(?string $bilyetType): string
    {
        // Map bilyet types to SAP payment methods
        // C = Check, T = Transfer, etc.
        $mapping = [
            'cek' => 'C',
            'bg' => 'C', // Bilyet Giro treated as Check
            'loa' => 'T', // Letter of Authority as Transfer
        ];

        return $mapping[$bilyetType ?? 'bg'] ?? 'C';
    }

    protected function buildComments(): string
    {
        $loan = $this->installment->loan;
        $paymentMethodLabel = $this->installment->payment_method === 'bilyet' ? 'Bilyet' : 'Auto-Debit';

        return "Payment for Loan: {$loan->loan_code} - Installment #{$this->installment->angsuran_ke} ({$paymentMethodLabel})";
    }

    public function validate(): array
    {
        $errors = [];

        if (!$this->installment->sap_ap_doc_entry) {
            $errors[] = 'AP Invoice must be created before creating payment';
        }

        if ($this->installment->sap_payment_doc_num) {
            $errors[] = 'Payment already created. Cannot resubmit.';
        }

        if ($this->installment->payment_method === 'bilyet') {
            if (!$this->bilyet) {
                $errors[] = 'Bilyet is required for bilyet payment method';
            } elseif ($this->bilyet->status !== 'cair') {
                $errors[] = 'Bilyet status must be "cair" before creating payment';
            } elseif (!$this->bilyet->giro_id) {
                $errors[] = 'Bilyet must be linked to a giro';
            } else {
                $giro = $this->bilyet->giro;
                if (!$giro || !$giro->sap_account) {
                    $errors[] = 'Giro must have SAP account mapping (sap_account)';
                }
            }
        }

        if ($this->installment->payment_method === 'auto_debit') {
            if (!$this->installment->paid_date) {
                $errors[] = 'Installment must be marked as paid before creating payment';
            }

            if (!$this->installment->account_id) {
                $errors[] = 'Installment must have account_id for auto-debit payment';
            } else {
                $account = $this->installment->account;
                if (!$account || !$account->sap_account) {
                    $errors[] = 'Account must have SAP account mapping (sap_account)';
                }
            }
        }

        $loan = $this->installment->loan;
        if (!$loan || !$loan->creditor_id) {
            $errors[] = 'Loan must be linked to a creditor';
        } else {
            $creditor = $loan->creditor;
            if (!$creditor || (empty($creditor->code) && empty($creditor->name))) {
                $errors[] = 'Creditor must have SAP code or name';
            }
        }

        return $errors;
    }
}
