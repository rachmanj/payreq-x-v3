<?php

namespace App\Services;

use App\Models\Faktur;
use Carbon\Carbon;

class SapArInvoiceJeBuilder
{
    protected Faktur $faktur;
    protected array $config;
    protected ?Carbon $jePostingDate = null;
    protected ?Carbon $jeTaxDate = null;
    protected ?Carbon $jeDueDate = null;

    public function __construct(Faktur $faktur, ?Carbon $jePostingDate = null, ?Carbon $jeTaxDate = null, ?Carbon $jeDueDate = null)
    {
        $this->faktur = $faktur;
        $this->config = config('services.sap.ar_invoice', []);
        
        // Set custom dates or use defaults
        $invoiceDate = Carbon::parse($this->faktur->invoice_date);
        $this->jePostingDate = $jePostingDate ?? $this->getDefaultJePostingDate($invoiceDate);
        $this->jeTaxDate = $jeTaxDate ?? $this->jePostingDate;
        $this->jeDueDate = $jeDueDate ?? $this->jePostingDate;
    }

    /**
     * Get default JE posting date: previous end of month of AR Invoice posting date
     */
    protected function getDefaultJePostingDate(Carbon $invoiceDate): Carbon
    {
        // If faktur has saved JE dates, use them
        if ($this->faktur->je_posting_date) {
            return Carbon::parse($this->faktur->je_posting_date);
        }
        
        // Default: previous end of month
        return $invoiceDate->copy()->subMonth()->endOfMonth();
    }

    public function build(): array
    {
        $customer = $this->faktur->customer;

        // Revenue account: selectable (41101 or 41201)
        $revenueAccountCode = $this->faktur->revenue_account_code ?? ($this->config['default_revenue_account'] ?? '41101');

        // AR Account: fixed at 491 (Perantara Pendapatan Kontrak)
        $arAccountCode = $this->config['default_ar_account'] ?? '491';

        // Use DPP amount only (not total with tax)
        $dppAmount = (float) $this->faktur->dpp;

        // Get project from customer or faktur
        $projectCode = $this->faktur->project ?? $customer->project;

        // Get department from customer default (Option B)
        $departmentCode = $this->getDepartmentCode();

        $journalEntry = [
            'ReferenceDate' => $this->jePostingDate->format('Y-m-d'),
            'TaxDate' => $this->jeTaxDate->format('Y-m-d'),
            'DueDate' => $this->jeDueDate->format('Y-m-d'),
            'Memo' => $this->buildMemo(),
            'JournalEntryLines' => [
                // Line 1: Credit Revenue
                [
                    'AccountCode' => $revenueAccountCode, // 41101 or 41201
                    'Credit' => $dppAmount,
                    'Debit' => 0.0,
                    'LineMemo' => "Revenue for Invoice: {$this->faktur->invoice_no}",
                    'ProjectCode' => $projectCode,
                    'CostingCode' => $departmentCode,
                    'Reference1' => $this->faktur->invoice_no,
                    'Reference2' => $this->faktur->sap_ar_doc_num ?? '',
                ],
                // Line 2: Debit AR
                [
                    'AccountCode' => $arAccountCode, // 491 - Perantara Pendapatan Kontrak (fixed)
                    'Debit' => $dppAmount,
                    'Credit' => 0.0,
                    'LineMemo' => "AR for Invoice: {$this->faktur->invoice_no}",
                    'ProjectCode' => $projectCode,
                    'CostingCode' => $departmentCode,
                    'Reference1' => $this->faktur->invoice_no,
                    'Reference2' => $this->faktur->sap_ar_doc_num ?? '',
                ],
            ],
        ];

        return $journalEntry;
    }

    protected function getDepartmentCode(): ?string
    {
        // Option B: Use default department from customer
        $customer = $this->faktur->customer;
        if ($customer && $customer->default_department_code) {
            return $customer->default_department_code;
        }

        // Fallback: Use system default department code
        return $this->config['default_department_code'] ?? '60';
    }

    protected function buildMemo(): string
    {
        $memo = "AR Invoice JE - Invoice: {$this->faktur->invoice_no}";
        if ($this->faktur->sap_ar_doc_num) {
            $memo .= " | AR Doc: {$this->faktur->sap_ar_doc_num}";
        }
        return $memo;
    }

    public function getPreviewData(): array
    {
        $customer = $this->faktur->customer;
        $invoiceDate = Carbon::parse($this->faktur->invoice_date);
        $revenueAccountCode = $this->faktur->revenue_account_code ?? ($this->config['default_revenue_account'] ?? '41101');
        $arAccountCode = $this->config['default_ar_account'] ?? '491';
        $dppAmount = (float) $this->faktur->dpp;
        $projectCode = $this->faktur->project ?? $customer->project;
        $departmentCode = $this->getDepartmentCode();

        return [
            'journal_entry' => [
                'dates' => [
                    'posting_date' => $this->jePostingDate->format('Y-m-d'),
                    'tax_date' => $this->jeTaxDate->format('Y-m-d'),
                    'due_date' => $this->jeDueDate->format('Y-m-d'),
                ],
                'amounts' => [
                    'dpp' => $dppAmount,
                ],
                'accounts' => [
                    'revenue_account' => $revenueAccountCode,
                    'ar_account' => $arAccountCode,
                ],
                'project' => $projectCode,
                'department' => $departmentCode,
                'memo' => $this->buildMemo(),
            ],
        ];
    }

    public function validate(): array
    {
        $errors = [];

        if (empty($this->faktur->sap_ar_doc_num)) {
            $errors[] = 'AR Invoice must be created before Journal Entry';
        }

        if (empty($this->faktur->dpp) || $this->faktur->dpp <= 0) {
            $errors[] = 'DPP amount is required for Journal Entry';
        }

        if ($this->faktur->sap_je_num) {
            $errors[] = 'Journal Entry already created. Cannot resubmit.';
        }

        // Validate revenue account code
        $revenueAccount = $this->faktur->revenue_account_code ?? ($this->config['default_revenue_account'] ?? '41101');
        if (!in_array($revenueAccount, ['41101', '41201'])) {
            $errors[] = 'Invalid revenue account code. Must be 41101 or 41201.';
        }

        return $errors;
    }
}
