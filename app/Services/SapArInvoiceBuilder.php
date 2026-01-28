<?php

namespace App\Services;

use App\Models\Faktur;
use Carbon\Carbon;

class SapArInvoiceBuilder
{
    protected Faktur $faktur;
    protected array $config;
    protected ?string $itemCode = null;

    public function __construct(Faktur $faktur, ?string $itemCode = null)
    {
        $this->faktur = $faktur;
        $this->config = config('services.sap.ar_invoice', []);
        $this->itemCode = $itemCode;
    }

    public function build(): array
    {
        $customer = $this->faktur->customer;
        $invoiceDate = Carbon::parse($this->faktur->invoice_date);
        $today = Carbon::today();

        // Due date: 30 days after Faktur Date (not invoice_date)
        $fakturDate = $this->faktur->faktur_date
            ? Carbon::parse($this->faktur->faktur_date)
            : $invoiceDate;
        $dueDate = $fakturDate->copy()->addDays(30);

        // Determine posting date: SAP B1 has strict date validation
        // Priority: faktur_date (if valid) > invoice_date (if not future) > today
        // SAP B1 typically doesn't allow posting dates more than 1-2 years in the past or in the future
        $maxPastYears = 2; // SAP B1 usually allows up to 2 years in the past
        $postingDate = $invoiceDate;

        // Check if faktur_date is valid and within acceptable range
        if ($this->faktur->faktur_date) {
            $fakturDateParsed = Carbon::parse($this->faktur->faktur_date);
            $minAllowedDate = $today->copy()->subYears($maxPastYears);

            if ($fakturDateParsed->gte($minAllowedDate) && $fakturDateParsed->lte($today)) {
                // Use faktur_date if it's within the last 2 years and not in the future
                $postingDate = $fakturDateParsed;
            }
        }

        // If posting date is still invalid, adjust it
        $minAllowedDate = $today->copy()->subYears($maxPastYears);
        if ($postingDate->lt($minAllowedDate)) {
            // Too old, use minimum allowed date
            $postingDate = $minAllowedDate;
        } elseif ($postingDate->gt($today)) {
            // Future date, use today
            $postingDate = $today;
        }

        // Calculate WTax once for reuse
        $wtaxAmount = $this->calculateWTaxAmount();
        $wtaxCode = $this->config['default_wtax_code'] ?? '';

        // G/L Account-based structure
        $invoice = [
            'CardCode' => $customer->code, // SAP Business Partner Code
            'DocDate' => $postingDate->format('Y-m-d'),
            'DocDueDate' => $dueDate->format('Y-m-d'),
            'TaxDate' => $postingDate->format('Y-m-d'),
            'DocCurrency' => $this->getCurrency(),
            'DocRate' => $this->faktur->kurs ?? 1.0,

            // Contract No = NumAtCard (Invoice Number)
            'NumAtCard' => $this->faktur->invoice_no,

            // Order No
            'U_MIS_InvOrderNo' => $this->faktur->invoice_no,

            // Faktur Pajak fields
            'U_MIS_FPNum' => $this->faktur->faktur_no,
            'U_MIS_FPDate' => $this->faktur->faktur_date
                ? Carbon::parse($this->faktur->faktur_date)->format('Y-m-d')
                : null,

            // Authorized Names and Kode Transaksi FP
            'U_MIS_Signature1' => $this->config['faktur_pajak']['authorized_name_invoice'] ?? '',
            'U_MIS_Signature2' => $this->config['faktur_pajak']['authorized_name_faktur_pajak'] ?? '',
            'U_MIS_FPTransCode' => $this->config['faktur_pajak']['kode_transaksi_fp'] ?? '01',

            // Bank Accounts
            'U_MIS_BankCode' => $this->config['bank_accounts']['usd']['bank_name'] ?? '',
            'U_MIS_BankAcctUSD' => $this->config['bank_accounts']['usd']['bank_account'] ?? '',
            'U_MIS_BankCodeIDR' => $this->config['bank_accounts']['idr']['bank_name'] ?? '',
            'U_MIS_BankAcctIDR' => $this->config['bank_accounts']['idr']['bank_account'] ?? '',

            'Comments' => $this->buildComments(),
            'DocumentLines' => $this->buildDocumentLines(),
        ];

        // Set WTax configuration at document level using WithholdingTaxDataCollection
        // According to SAP B1 Service Layer API, use WithholdingTaxDataCollection with WTCode and WTAmount
        if (!empty($wtaxCode) && $wtaxAmount > 0) {
            $invoice['WithholdingTaxDataCollection'] = [
                [
                    'WTCode' => $wtaxCode,
                    'WTAmount' => $wtaxAmount, // Explicitly set WTAmount (2% of DPP)
                ],
            ];
        }

        return $invoice;
    }

    protected function calculateWTaxAmount(): float
    {
        // Use existing wtax_amount if set, otherwise calculate based on configured percentage
        if ($this->faktur->wtax_amount && $this->faktur->wtax_amount > 0) {
            return (float) $this->faktur->wtax_amount;
        }

        // Calculate WTax based on configured percentage (default 2% of DPP)
        $wtaxPercentage = ($this->config['wtax_percentage'] ?? 2) / 100;
        return (float) $this->faktur->dpp * $wtaxPercentage;
    }

    protected function buildDocumentLines(): array
    {
        // Use AR Account (11401039 - Piutang Usaha Belum Ditagih) instead of Revenue Account as per user requirement
        $arAccountCode = $this->config['default_ar_account'] ?? '11401039';

        // Use passed itemCode, or fallback to configured default, or 'SERVICE'
        $itemCode = $this->itemCode ?? $this->config['default_item_code'] ?? 'SERVICE';

        // Unit Price = DPP only (SAP B1 will calculate VAT and WTax automatically)
        $dppAmount = (float) $this->faktur->dpp;

        $customer = $this->faktur->customer;
        $wtaxCode = $this->config['default_wtax_code'] ?? '';

        $line = [
            'ItemCode' => $itemCode, // Required by SAP B1 even for G/L Account-based lines
            'AccountCode' => $arAccountCode, // AR Account (11401039) - Piutang Usaha Belum Ditagih
            'LineTotal' => $dppAmount, // Unit Price = DPP only (VAT and WTax will be calculated by SAP B1)
            'UseBaseUnits' => 'N', // Indicates G/L Account-based line (no units)
            // Department: Default to 60 (Production), fallback to customer default
            'CostingCode' => $customer->default_department_code
                ?? $this->config['default_department_code']
                ?? '60',
        ];

        // Add WTax configuration at line level
        // Mark line as WTax liable (WTax amount is set at document level via WithholdingTaxData)
        if (!empty($wtaxCode)) {
            $line['WTaxCode'] = $wtaxCode;
            $line['WTaxLiable'] = 'Y';
            // WTax amount is handled at document level via WithholdingTaxData collection
        }

        // Add Project if available
        if ($this->faktur->project) {
            $line['ProjectCode'] = $this->faktur->project;
        } elseif ($customer->project) {
            $line['ProjectCode'] = $customer->project;
        }

        // Add description
        if ($this->faktur->invoice_no) {
            $line['LineMemo'] = "Invoice No: {$this->faktur->invoice_no}";
        }

        return [$line];
    }

    protected function getCurrency(): string
    {
        // Default to IDR, can be enhanced based on kurs
        return 'IDR';
    }

    protected function buildComments(): string
    {
        $comments = "Invoice No: {$this->faktur->invoice_no}";
        if ($this->faktur->remarks) {
            $comments .= "\n" . $this->faktur->remarks;
        }
        return $comments;
    }

    public function getPreviewData(): array
    {
        $customer = $this->faktur->customer;
        $invoiceDate = Carbon::parse($this->faktur->invoice_date);
        $today = Carbon::today();

        // Due date: 30 days after Faktur Date
        $fakturDate = $this->faktur->faktur_date
            ? Carbon::parse($this->faktur->faktur_date)
            : $invoiceDate;
        $dueDate = $fakturDate->copy()->addDays(30);

        // Use same date logic as build() method for consistency
        $maxPastYears = 2;
        $postingDate = $invoiceDate;

        if ($this->faktur->faktur_date) {
            $fakturDateParsed = Carbon::parse($this->faktur->faktur_date);
            $minAllowedDate = $today->copy()->subYears($maxPastYears);

            if ($fakturDateParsed->gte($minAllowedDate) && $fakturDateParsed->lte($today)) {
                $postingDate = $fakturDateParsed;
            }
        }

        $minAllowedDate = $today->copy()->subYears($maxPastYears);
        if ($postingDate->lt($minAllowedDate)) {
            $postingDate = $minAllowedDate;
        } elseif ($postingDate->gt($today)) {
            $postingDate = $today;
        }

        $wtaxAmount = $this->calculateWTaxAmount();
        $wtaxCode = $this->config['default_wtax_code'] ?? '';
        $arAccountCode = $this->config['default_ar_account'] ?? '11401039'; // AR Invoice line uses AR account (11401039 - Piutang Usaha Belum Ditagih)
        $revenueAccountCode = $this->faktur->revenue_account_code ?? ($this->config['default_revenue_account'] ?? '41101'); // For JE reference
        $departmentCode = $customer->default_department_code ?? $this->config['default_department_code'] ?? '60';
        $projectCode = $this->faktur->project ?? $customer->project;

        return [
            'ar_invoice' => [
                'customer' => [
                    'code' => $customer->code ?? '',
                    'name' => $customer->name ?? '',
                ],
                'dates' => [
                    'posting_date' => $postingDate->format('Y-m-d'),
                    'due_date' => $dueDate->format('Y-m-d'),
                    'tax_date' => $postingDate->format('Y-m-d'),
                ],
                'invoice_no' => $this->faktur->invoice_no,
                'faktur_no' => $this->faktur->faktur_no,
                'faktur_date' => $this->faktur->faktur_date ? Carbon::parse($this->faktur->faktur_date)->format('Y-m-d') : null,
                'amounts' => [
                    'dpp' => (float) $this->faktur->dpp,
                    'ppn' => (float) ($this->faktur->ppn ?? 0),
                    'wtax_amount' => $wtaxAmount,
                    'wtax_code' => $wtaxCode,
                    'total' => (float) $this->faktur->dpp + (float) ($this->faktur->ppn ?? 0) - $wtaxAmount,
                ],
                'accounts' => [
                    'ar_account' => $arAccountCode, // AR Invoice line uses AR account (11401039 - Piutang Usaha Belum Ditagih)
                    'revenue_account' => $revenueAccountCode, // For reference (used in JE)
                ],
                'project' => $projectCode,
                'department' => $departmentCode,
                'currency' => $this->getCurrency(),
                'kurs' => $this->faktur->kurs ?? 1.0,
                'item_code' => $this->itemCode ?? $this->config['default_item_code'] ?? 'SERVICE',
            ],
        ];
    }

    public function validate(): array
    {
        $errors = [];

        if ($this->faktur->type !== 'sales') {
            $errors[] = 'Only sales fakturs can be submitted to SAP B1';
        }

        if (empty($this->faktur->faktur_no) || empty($this->faktur->faktur_date)) {
            $errors[] = 'Faktur number and date must be filled before SAP submission';
        }

        if (empty($this->faktur->customer->code)) {
            $errors[] = 'Customer must have SAP Business Partner Code';
        }

        if (empty($this->faktur->dpp) || $this->faktur->dpp <= 0) {
            $errors[] = 'DPP (Tax Base) must be greater than zero';
        }

        if ($this->faktur->sap_ar_doc_num) {
            $errors[] = 'AR Invoice already created. Cannot resubmit.';
        }

        // Validate revenue account code
        $validRevenueAccounts = ['41101', '41201'];
        $revenueAccount = $this->faktur->revenue_account_code ?? ($this->config['default_revenue_account'] ?? '41101');
        if (!in_array($revenueAccount, $validRevenueAccounts)) {
            $errors[] = 'Invalid revenue account code. Must be 41101 or 41201.';
        }

        // Validate dates are within SAP B1 acceptable range
        $today = Carbon::today();
        $maxPastYears = 2;
        $minAllowedDate = $today->copy()->subYears($maxPastYears);

        if ($this->faktur->faktur_date) {
            $fakturDate = Carbon::parse($this->faktur->faktur_date);
            if ($fakturDate->lt($minAllowedDate)) {
                $errors[] = "Faktur date ({$fakturDate->format('Y-m-d')}) is too old. SAP B1 allows dates within the last {$maxPastYears} years only.";
            } elseif ($fakturDate->gt($today)) {
                $errors[] = "Faktur date ({$fakturDate->format('Y-m-d')}) cannot be in the future.";
            }
        }

        if ($this->faktur->invoice_date) {
            $invoiceDate = Carbon::parse($this->faktur->invoice_date);
            if ($invoiceDate->lt($minAllowedDate)) {
                $errors[] = "Invoice date ({$invoiceDate->format('Y-m-d')}) is too old. SAP B1 allows dates within the last {$maxPastYears} years only.";
            } elseif ($invoiceDate->gt($today)) {
                $errors[] = "Invoice date ({$invoiceDate->format('Y-m-d')}) cannot be in the future.";
            }
        }

        return $errors;
    }
}
