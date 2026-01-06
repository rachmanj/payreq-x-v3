# SAP B1 AR Invoice + Journal Entry Integration - Updated Concept

## Executive Summary

Based on the SAP B1 screenshots provided, this integration requires **TWO sequential SAP B1 document creations**:

1. **AR Invoice** (`/Invoices`) - Creates the customer invoice document
2. **Journal Entry** (`/JournalEntries`) - Creates the revenue recognition entry

Both documents must be created automatically after faktur completion, with proper linking and data consistency.

---

## Understanding from Screenshots

### AR Invoice Screenshot Analysis

**Key Fields Observed:**
- **Invoice No**: 2025
- **Customer Code**: `CGRPKIDR01` (GRAHA PANCA KARSA)
- **Posting Date**: 05.11.2025
- **Due Date**: 05.12.2025
- **Faktur Pajak No**: `04002500356887822`
- **Faktur Pajak Date**: 05.11.2025
- **G/L Account**: `11401039` (Piutang Usaha Belum Ditagih - AR Not Yet Billed)
- **Total**: IDR 33,766,392,808.11
- **Tax**: IDR 3,346,219,107.11
- **WTax Amount**: IDR 608,403,474.00
- **Project**: 022C (from contract/order reference)
- **Description**: `034/Arka-022C/XI/2025` (contract number)

**AR Invoice Structure:**
- Uses G/L Account `11401039` directly in document line (not item-based)
- Contains Faktur Pajak information (tax invoice number and date)
- References contract/order number
- Includes project information

### Journal Entry Screenshot Analysis

**Key Fields Observed:**
- **Journal Entry Number**: 257650595
- **Posting Date**: 31.10.2025
- **Project**: 022C
- **Department**: 60
- **Remarks**: `GPK No.034/Arka-022C/XI/202022C`

**Journal Entry Lines:**
1. **Line 1 (Credit)**:
   - Account: `41101` (Pendapatan Kontrak - Contract Revenue)
   - Credit: `30,420,173,701.00`
   - Project: 022C
   - Department: 60

2. **Line 2 (Debit)**:
   - Account: `11401039` (Piutang Usaha Belum Ditagih - AR Not Yet Billed)
   - Debit: `30,420,173,701.00`
   - Project: 022C
   - Department: 60

**Key Observations:**
- Journal Entry amount (`30,420,173,701.00`) matches DPP (Tax Base) from AR Invoice
- AR Invoice Total (`33,766,392,808.11`) = DPP + Tax - WTax
- Journal Entry only records DPP amount (revenue recognition)
- Both lines reference same Project (022C) and Department (60)
- Journal Entry references the AR Invoice via remarks

---

## Updated Integration Workflow

```
┌─────────────────┐
│  Faktur Model   │
│  (Sales Type)   │
│  - faktur_no    │
│  - faktur_date  │
│  - dpp          │
│  - ppn          │
└────────┬────────┘
         │
         │ Step 1: User clicks "Submit to SAP"
         ▼
┌─────────────────────────┐
│  Validation & Preparation│
│  - Check faktur_no       │
│  - Validate customer SAP │
│  - Get project/dept      │
└────────┬─────────────────┘
         │
         │ Step 2: Create AR Invoice
         ▼
┌─────────────────────────┐
│  SapArInvoiceBuilder     │
│  Build AR Invoice Payload│
│  - CardCode (customer)   │
│  - DocDate, DueDate      │
│  - NumAtCard (faktur_no)│
│  - G/L Account: 11401039│
│  - Amount: DPP + PPN    │
└────────┬─────────────────┘
         │
         │ POST /Invoices
         ▼
┌─────────────────────────┐
│  SAP B1 AR Invoice       │
│  Created                  │
│  Returns: DocNum, DocEntry│
└────────┬─────────────────┘
         │
         │ Step 3: Create Journal Entry
         ▼
┌─────────────────────────┐
│  SapArInvoiceJeBuilder   │
│  Build Journal Entry     │
│  Line 1 (Credit):        │
│  - Account: 41101         │
│  - Credit: DPP amount    │
│  - Project: from customer│
│  - Department: from cust│
│  Line 2 (Debit):         │
│  - Account: 11401039     │
│  - Debit: DPP amount      │
│  - Project: same          │
│  - Department: same       │
└────────┬─────────────────┘
         │
         │ POST /JournalEntries
         ▼
┌─────────────────────────┐
│  SAP B1 Journal Entry    │
│  Created                  │
│  Returns: JdtNum, DocEntry│
└────────┬─────────────────┘
         │
         │ Step 4: Update Local Records
         ▼
┌─────────────────────────┐
│  Update Faktur           │
│  - doc_num (AR Invoice) │
│  - sap_je_num (JE)      │
│  - sap_submitted_at      │
│  - sap_submission_status │
└─────────────────────────┘
```

---

## Critical Clarifications Needed

### 1. AR Invoice Structure

**Question**: Should AR Invoice use **G/L Account-based lines** (as shown: `11401039`) or **Item-based lines** (ItemCode like "TAXABLE", "VAT")?

**From Screenshot**: AR Invoice shows G/L Account `11401039` directly in the line, suggesting **account-based** structure.

**Recommendation**: Use G/L Account-based structure:
```json
{
  "DocumentLines": [
    {
      "AccountCode": "11401039",
      "LineTotal": 30420173701.00,
      "ProjectCode": "022C",
      "CostingCode": "60"
    }
  ]
}
```

**OR** if SAP requires item-based:
```json
{
  "DocumentLines": [
    {
      "ItemCode": "SERVICE",
      "AccountCode": "11401039",
      "Quantity": 1,
      "UnitPrice": 30420173701.00,
      "ProjectCode": "022C",
      "CostingCode": "60"
    }
  ]
}
```

**Action Required**: Confirm with SAP B1 administrator which structure is used.

### 2. Journal Entry Account Configuration

**Question**: Are these account codes fixed or configurable per customer/project?

**From Screenshot**:
- Revenue Account: `41101` (Pendapatan Kontrak)
- AR Account: `11401039` (Piutang Usaha Belum Ditagih)

**Recommendation**: 
- Make accounts configurable via:
  - Customer-level configuration (preferred)
  - Project-level configuration
  - System-wide defaults
- Add fields to `customers` table:
  - `revenue_account_code` (default: 41101)
  - `ar_account_code` (default: 11401039)

### 3. Project and Department Mapping

**Question**: How to determine Project and Department for Journal Entry?

**From Screenshot**: 
- Project: `022C` (from contract/order)
- Department: `60` (not visible in AR Invoice, but present in JE)

**Current Data Available**:
- `Customer->project` field exists
- Department not directly available in Faktur

**Recommendation**:
- **Project**: Use `Customer->project` field (already exists)
- **Department**: 
  - Option A: Add `department_id` to fakturs table
  - Option B: Use default department from customer
  - Option C: Use user's department (`auth()->user()->department_id`)

**Action Required**: Confirm source of Department code (60) for fakturs.

### 4. Amount Calculation

**Question**: What amount should be used in Journal Entry?

**From Screenshot**:
- AR Invoice Total: `33,766,392,808.11` (includes tax, minus WTax)
- Journal Entry Amount: `30,420,173,701.00` (DPP only)

**Analysis**:
- DPP = `30,420,173,701.00`
- PPN = `3,346,219,107.11` (11% of DPP)
- WTax = `608,403,474.00`
- Total = DPP + PPN - WTax = `33,158,193,334.11` (close to screenshot total)

**Recommendation**: 
- **Journal Entry**: Use **DPP amount only** (tax base, excluding VAT and WTax)
- This matches revenue recognition principle (revenue = DPP, VAT is liability)

### 5. WTax (Withholding Tax) Handling

**Question**: How should WTax be handled in the integration?

**From Screenshot**: WTax Amount: `IDR 608,403,474.00`

**Recommendation**:
- Include WTax in AR Invoice if faktur has WTax data
- Journal Entry should NOT include WTax (it's a separate liability)
- If WTax exists, may need additional JE line for WTax liability account

**Action Required**: Confirm if fakturs table has WTax field, or if it needs to be added.

### 6. Transaction Sequence & Error Handling

**Question**: What happens if AR Invoice succeeds but Journal Entry fails?

**Recommendation**: 
- **Option A (Recommended)**: Create both in single transaction
  - If JE fails, cancel/delete AR Invoice
  - Requires SAP B1 API support for cancellation
- **Option B**: Create AR Invoice first, then JE
  - If JE fails, mark faktur with partial status
  - Allow manual JE creation later
  - Store AR Invoice DocNum even if JE fails

**Action Required**: Confirm if SAP B1 Service Layer supports AR Invoice cancellation/deletion.

### 7. Date Handling

**Question**: Why is Journal Entry date (31.10.2025) different from AR Invoice date (05.11.2025)?

**From Screenshot**: 
- AR Invoice Posting Date: 05.11.2025
- Journal Entry Posting Date: 31.10.2025

**Recommendation**:
- Use **same posting date** for both documents (AR Invoice date)
- If different dates are required, add `je_posting_date` field to fakturs table
- Default: Use `invoice_date` for both

---

## Updated Implementation Recommendations

### Phase 1: Database Schema Updates

```php
// Migration: Add fields to fakturs table
Schema::table('fakturs', function (Blueprint $table) {
    // SAP AR Invoice tracking
    $table->string('sap_ar_doc_num')->nullable()->after('doc_num');
    $table->string('sap_ar_doc_entry')->nullable()->after('sap_ar_doc_num');
    
    // SAP Journal Entry tracking
    $table->string('sap_je_num')->nullable()->after('sap_ar_doc_entry');
    $table->string('sap_je_doc_entry')->nullable()->after('sap_je_num');
    
    // Submission tracking
    $table->enum('sap_submission_status', ['pending', 'ar_created', 'je_created', 'completed', 'failed'])
        ->nullable()->after('status');
    $table->integer('sap_submission_attempts')->default(0);
    $table->text('sap_submission_error')->nullable();
    $table->timestamp('sap_submitted_at')->nullable();
    $table->foreignId('sap_submitted_by')->nullable();
    
    // Project/Department for JE
    $table->string('project', 10)->nullable()->after('customer_id');
    $table->foreignId('department_id')->nullable()->after('project');
    
    // WTax (if not exists)
    $table->decimal('wtax_amount', 20, 2)->nullable()->after('ppn');
});
```

### Phase 2: Customer Model Enhancement

```php
// Migration: Add account configuration to customers table
Schema::table('customers', function (Blueprint $table) {
    $table->string('revenue_account_code', 20)->nullable()->after('code');
    $table->string('ar_account_code', 20)->nullable()->after('revenue_account_code');
    $table->string('default_department_code', 10)->nullable()->after('ar_account_code');
});
```

### Phase 3: Service Layer Implementation

#### 3.1 SapArInvoiceBuilder (Updated)

```php
<?php

namespace App\Services;

use App\Models\Faktur;
use Carbon\Carbon;

class SapArInvoiceBuilder
{
    protected Faktur $faktur;
    protected array $config;

    public function __construct(Faktur $faktur)
    {
        $this->faktur = $faktur;
        $this->config = config('services.sap.ar_invoice');
    }

    public function build(): array
    {
        $customer = $this->faktur->customer;
        $invoiceDate = Carbon::parse($this->faktur->invoice_date);
        $dueDate = $invoiceDate->copy()->addDays($this->config['default_payment_terms']);

        // Use G/L Account-based structure (as per screenshot)
        $invoice = [
            'CardCode' => $customer->code, // SAP Business Partner Code
            'DocDate' => $invoiceDate->format('Y-m-d'),
            'DocDueDate' => $dueDate->format('Y-m-d'),
            'TaxDate' => $invoiceDate->format('Y-m-d'),
            'DocCurrency' => $this->getCurrency(),
            'DocRate' => $this->faktur->kurs ?? 1.0,
            'NumAtCard' => $this->faktur->faktur_no, // Faktur Pajak Number
            'Comments' => $this->buildComments(),
            'DocumentLines' => $this->buildDocumentLines(),
        ];

        // Add WTax if exists
        if ($this->faktur->wtax_amount && $this->faktur->wtax_amount > 0) {
            $invoice['WTaxAmount'] = (float) $this->faktur->wtax_amount;
        }

        return $invoice;
    }

    protected function buildDocumentLines(): array
    {
        $customer = $this->faktur->customer;
        $arAccountCode = $customer->ar_account_code ?? $this->config['default_ar_account'];
        
        $line = [
            'AccountCode' => $arAccountCode, // 11401039 from screenshot
            'LineTotal' => (float) $this->faktur->dpp + (float) ($this->faktur->ppn ?? 0),
        ];

        // Add Project if available
        if ($this->faktur->project) {
            $line['ProjectCode'] = $this->faktur->project;
        }

        // Add Department/Cost Center if available
        if ($this->faktur->department_id) {
            $department = $this->faktur->department;
            if ($department && $department->sap_code) {
                $line['CostingCode'] = $department->sap_code;
            }
        }

        // Add description
        if ($this->faktur->invoice_no) {
            $line['LineMemo'] = "Invoice No: {$this->faktur->invoice_no}";
        }

        return [$line];
    }

    protected function getCurrency(): string
    {
        return 'IDR'; // Default, can be enhanced based on kurs
    }

    protected function buildComments(): string
    {
        $comments = "Invoice No: {$this->faktur->invoice_no}";
        if ($this->faktur->remarks) {
            $comments .= "\n" . $this->faktur->remarks;
        }
        return $comments;
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

        return $errors;
    }
}
```

#### 3.2 SapArInvoiceJeBuilder (New)

```php
<?php

namespace App\Services;

use App\Models\Faktur;
use Carbon\Carbon;

class SapArInvoiceJeBuilder
{
    protected Faktur $faktur;
    protected array $config;

    public function __construct(Faktur $faktur)
    {
        $this->faktur = $faktur;
        $this->config = config('services.sap.ar_invoice');
    }

    public function build(): array
    {
        $customer = $this->faktur->customer;
        $invoiceDate = Carbon::parse($this->faktur->invoice_date);
        
        $revenueAccountCode = $customer->revenue_account_code ?? $this->config['default_revenue_account'];
        $arAccountCode = $customer->ar_account_code ?? $this->config['default_ar_account'];
        
        $dppAmount = (float) $this->faktur->dpp;

        $journalEntry = [
            'ReferenceDate' => $invoiceDate->format('Y-m-d'),
            'TaxDate' => $invoiceDate->format('Y-m-d'),
            'DueDate' => $invoiceDate->format('Y-m-d'),
            'Memo' => $this->buildMemo(),
            'JournalEntryLines' => [
                // Line 1: Credit Revenue
                [
                    'AccountCode' => $revenueAccountCode, // 41101
                    'Credit' => $dppAmount,
                    'Debit' => 0.0,
                    'LineMemo' => "Revenue for Invoice: {$this->faktur->invoice_no}",
                    'ProjectCode' => $this->faktur->project ?? $customer->project,
                    'CostingCode' => $this->getDepartmentCode(),
                    'Reference1' => $this->faktur->invoice_no,
                    'Reference2' => $this->faktur->sap_ar_doc_num ?? '',
                ],
                // Line 2: Debit AR
                [
                    'AccountCode' => $arAccountCode, // 11401039
                    'Debit' => $dppAmount,
                    'Credit' => 0.0,
                    'LineMemo' => "AR for Invoice: {$this->faktur->invoice_no}",
                    'ProjectCode' => $this->faktur->project ?? $customer->project,
                    'CostingCode' => $this->getDepartmentCode(),
                    'Reference1' => $this->faktur->invoice_no,
                    'Reference2' => $this->faktur->sap_ar_doc_num ?? '',
                ],
            ],
        ];

        return $journalEntry;
    }

    protected function getDepartmentCode(): ?string
    {
        if ($this->faktur->department_id) {
            $department = $this->faktur->department;
            return $department->sap_code ?? null;
        }

        // Fallback to customer default
        if ($this->faktur->customer->default_department_code) {
            return $this->faktur->customer->default_department_code;
        }

        return null;
    }

    protected function buildMemo(): string
    {
        $memo = "AR Invoice JE - Invoice: {$this->faktur->invoice_no}";
        if ($this->faktur->sap_ar_doc_num) {
            $memo .= " | AR Doc: {$this->faktur->sap_ar_doc_num}";
        }
        return $memo;
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

        return $errors;
    }
}
```

### Phase 4: Controller Implementation

```php
// Add to VatController

public function submitToSap(Request $request, Faktur $faktur)
{
    // Permission check
    if (!auth()->user()->can('submit-sap-ar-invoice')) {
        abort(403, 'Unauthorized');
    }

    DB::beginTransaction();
    try {
        // Step 1: Build and validate AR Invoice
        $arInvoiceBuilder = new SapArInvoiceBuilder($faktur);
        $arErrors = $arInvoiceBuilder->validate();
        
        if (!empty($arErrors)) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . implode(', ', $arErrors),
            ], 422);
        }

        $arInvoiceData = $arInvoiceBuilder->build();
        
        // Step 2: Create AR Invoice in SAP B1
        $sapService = app(SapService::class);
        $arResult = $sapService->createArInvoice($arInvoiceData);
        
        if (!($arResult['success'] ?? false)) {
            throw new \Exception('Failed to create AR Invoice: ' . ($arResult['message'] ?? 'Unknown error'));
        }

        // Step 3: Update faktur with AR Invoice info
        $faktur->sap_ar_doc_num = $arResult['doc_num'];
        $faktur->sap_ar_doc_entry = $arResult['doc_entry'];
        $faktur->sap_submission_status = 'ar_created';
        $faktur->save();

        // Step 4: Build and validate Journal Entry
        $jeBuilder = new SapArInvoiceJeBuilder($faktur);
        $jeErrors = $jeBuilder->validate();
        
        if (!empty($jeErrors)) {
            // AR Invoice created but JE failed - mark as partial
            $faktur->sap_submission_status = 'ar_created';
            $faktur->sap_submission_error = 'JE Validation failed: ' . implode(', ', $jeErrors);
            $faktur->save();
            
            DB::commit();
            
            return response()->json([
                'success' => false,
                'message' => 'AR Invoice created but Journal Entry failed: ' . implode(', ', $jeErrors),
                'ar_doc_num' => $arResult['doc_num'],
            ], 422);
        }

        $jeData = $jeBuilder->build();
        
        // Step 5: Create Journal Entry in SAP B1
        $jeResult = $sapService->createJournalEntry($jeData);
        
        if (!($jeResult['success'] ?? false)) {
            // AR Invoice created but JE failed
            $faktur->sap_submission_status = 'ar_created';
            $faktur->sap_submission_error = 'JE Creation failed: ' . ($jeResult['message'] ?? 'Unknown error');
            $faktur->save();
            
            DB::commit();
            
            return response()->json([
                'success' => false,
                'message' => 'AR Invoice created but Journal Entry failed: ' . ($jeResult['message'] ?? 'Unknown error'),
                'ar_doc_num' => $arResult['doc_num'],
            ], 422);
        }

        // Step 6: Update faktur with Journal Entry info
        $faktur->sap_je_num = $jeResult['journal_number'];
        $faktur->sap_je_doc_entry = $jeResult['doc_entry'];
        $faktur->sap_submission_status = 'completed';
        $faktur->sap_submitted_at = now();
        $faktur->sap_submitted_by = auth()->id();
        $faktur->sap_submission_attempts = ($faktur->sap_submission_attempts ?? 0) + 1;
        $faktur->save();

        // Step 7: Log submission
        SapSubmissionLog::create([
            'faktur_id' => $faktur->id,
            'document_type' => 'ar_invoice',
            'status' => 'success',
            'sap_doc_num' => $arResult['doc_num'],
            'sap_response' => json_encode($arResult['data']),
            'attempt_number' => $faktur->sap_submission_attempts,
            'submitted_by' => auth()->id(),
        ]);

        SapSubmissionLog::create([
            'faktur_id' => $faktur->id,
            'document_type' => 'journal_entry',
            'status' => 'success',
            'sap_doc_num' => $jeResult['journal_number'],
            'sap_response' => json_encode($jeResult['data']),
            'attempt_number' => $faktur->sap_submission_attempts,
            'submitted_by' => auth()->id(),
        ]);

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'AR Invoice and Journal Entry created successfully',
            'ar_doc_num' => $arResult['doc_num'],
            'je_num' => $jeResult['journal_number'],
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        
        // Log error
        Log::error('SAP AR Invoice submission failed', [
            'faktur_id' => $faktur->id,
            'error' => $e->getMessage(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to submit to SAP B1: ' . $e->getMessage(),
        ], 500);
    }
}
```

---

## Configuration Requirements

### Environment Variables

```env
# SAP B1 AR Invoice Configuration
SAP_AR_INVOICE_DEFAULT_PAYMENT_TERMS=15
SAP_AR_INVOICE_DEFAULT_REVENUE_ACCOUNT=41101
SAP_AR_INVOICE_DEFAULT_AR_ACCOUNT=11401039
```

### Config File

```php
// config/services.php
'ar_invoice' => [
    'default_payment_terms' => env('SAP_AR_INVOICE_DEFAULT_PAYMENT_TERMS', 15),
    'default_revenue_account' => env('SAP_AR_INVOICE_DEFAULT_REVENUE_ACCOUNT', '41101'),
    'default_ar_account' => env('SAP_AR_INVOICE_DEFAULT_AR_ACCOUNT', '11401039'),
],
```

---

## Questions for Review

1. **AR Invoice Structure**: G/L Account-based (as screenshot) or Item-based?
2. **Account Codes**: Fixed (41101, 11401039) or configurable per customer?
3. **Department Source**: Where does Department code (60) come from?
4. **WTax Handling**: Does fakturs table have WTax field, or need to add?
5. **Transaction Safety**: Should AR Invoice be cancelled if JE fails?
6. **Date Consistency**: Use same date for both documents or allow different dates?

---

**Document Version**: 2.0  
**Last Updated**: 2025-01-XX  
**Status**: Updated based on SAP B1 screenshots - Pending Review
