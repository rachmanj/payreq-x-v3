<?php

namespace App\Services;

use App\Models\Installment;
use Carbon\Carbon;

class SapApInvoiceBuilder
{
    protected Installment $installment;
    protected array $config;

    public function __construct(Installment $installment)
    {
        $this->installment = $installment;
        $this->config = config('services.sap.ap_invoice', []);
    }

    public function build(): array
    {
        $loan = $this->installment->loan;
        $creditor = $loan->creditor;

        // Get SAP CardCode from relationship
        if (!$creditor->sapBusinessPartner) {
            throw new \Exception(
                "Creditor '{$creditor->name}' is not linked to a SAP Business Partner. " .
                "Please link it to a vendor in SAP before creating AP Invoice."
            );
        }

        $sapPartner = $creditor->sapBusinessPartner;
        $sapCode = $sapPartner->code;

        // Validate SAP partner is active
        if (!$sapPartner->active) {
            throw new \Exception(
                "SAP Business Partner '{$sapCode}' ({$sapPartner->name}) is inactive. Cannot create AP Invoice."
            );
        }

        // Validate SAP partner is a supplier/vendor
        if (!$sapPartner->isSupplier()) {
            throw new \Exception(
                "SAP Business Partner '{$sapCode}' ({$sapPartner->name}) is not a supplier/vendor. " .
                "AP Invoices can only be created for suppliers. Current type: {$sapPartner->type}"
            );
        }

        $dueDate = $this->installment->due_date
            ? Carbon::parse($this->installment->due_date)
            : Carbon::today();

        $postingDate = $dueDate->copy();
        $today = Carbon::today();

        // Ensure posting date is not in the future
        if ($postingDate->gt($today)) {
            $postingDate = $today;
        }

        $invoice = [
            'CardCode' => $sapCode, // Now guaranteed to exist and be valid
            'DocDate' => $postingDate->format('Y-m-d'),
            'DocDueDate' => $dueDate->format('Y-m-d'),
            'TaxDate' => $postingDate->format('Y-m-d'),
            'DocCurrency' => 'IDR',
            'DocRate' => 1.0,
            'NumAtCard' => $this->buildReferenceNumber(),
            'Comments' => $this->buildComments(),
            'DocumentLines' => $this->buildDocumentLines(),
        ];

        return $invoice;
    }

    protected function buildDocumentLines(): array
    {
        $accountCode = $this->config['default_account'] ?? '21101';
        $loan = $this->installment->loan;
        $amount = (float) $this->installment->bilyet_amount;

        $line = [
            'AccountCode' => $accountCode,
            'LineTotal' => $amount,
            'UseBaseUnits' => 'N',
        ];

        // Add Project if available
        if ($loan->project) {
            $line['ProjectCode'] = $loan->project;
        }

        // Add description
        $line['LineMemo'] = "Loan: {$loan->loan_code} - Installment #{$this->installment->angsuran_ke}";

        return [$line];
    }

    protected function buildComments(): string
    {
        $loan = $this->installment->loan;
        return "Loan: {$loan->loan_code} - Installment #{$this->installment->angsuran_ke}";
    }

    protected function buildReferenceNumber(): string
    {
        $loan = $this->installment->loan;
        return "LOAN-{$loan->loan_code}-INST-{$this->installment->angsuran_ke}";
    }

    public function validate(): array
    {
        $errors = [];

        if (!$this->installment->loan_id) {
            $errors[] = 'Installment must be linked to a loan';
        }

        $loan = $this->installment->loan;
        if (!$loan) {
            $errors[] = 'Loan not found';
            return $errors;
        }

        if (!$loan->creditor_id) {
            $errors[] = 'Loan must be linked to a creditor';
        }

        $creditor = $loan->creditor;
        if (!$creditor) {
            $errors[] = 'Creditor not found';
            return $errors;
        }

        // Validate creditor has SAP Business Partner link
        if (!$creditor->sapBusinessPartner) {
            $errors[] = 'Creditor must be linked to a SAP Business Partner (Supplier/Vendor)';
        } else {
            $sapPartner = $creditor->sapBusinessPartner;
            
            // Validate SAP partner is active
            if (!$sapPartner->active) {
                $errors[] = "Linked SAP Business Partner '{$sapPartner->code}' is inactive";
            }
            
            // Validate SAP partner is a supplier/vendor
            if (!$sapPartner->isSupplier()) {
                $errors[] = "Linked SAP Business Partner '{$sapPartner->code}' must be a Supplier/Vendor type (current: {$sapPartner->type})";
            }
            
            // Validate SAP code exists
            if (empty($sapPartner->code)) {
                $errors[] = 'Linked SAP Business Partner has no CardCode';
            }
        }

        if (!$this->installment->due_date) {
            $errors[] = 'Installment must have a due date';
        }

        if (empty($this->installment->bilyet_amount) || $this->installment->bilyet_amount <= 0) {
            $errors[] = 'Installment amount must be greater than zero';
        }

        if ($this->installment->sap_ap_doc_num) {
            $errors[] = 'AP Invoice already created. Cannot resubmit.';
        }

        // Validate payment method
        if (!in_array($this->installment->payment_method, ['bilyet', 'auto_debit'])) {
            $errors[] = 'AP Invoice can only be created for bilyet or auto-debit payment methods';
        }

        return $errors;
    }
}
