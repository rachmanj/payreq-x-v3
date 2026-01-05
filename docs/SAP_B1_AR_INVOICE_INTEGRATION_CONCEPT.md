# SAP B1 AR Invoice Integration Concept & Recommendations

## Executive Summary

This document provides a comprehensive analysis and integration concept for connecting the Faktur (Sales Invoice) module with SAP B1 Accounts Receivable (AR) Invoice functionality. The integration will enable automatic creation of AR Invoices in SAP B1 when sales fakturs are completed, eliminating manual data entry and ensuring data consistency between systems.

---

## Current State Analysis

### 1. Faktur Module Structure

**Database Schema** (`fakturs` table):
- **Basic Info**: `id`, `customer_id`, `invoice_no`, `invoice_date`
- **Tax Info**: `dpp` (Tax Base), `ppn` (VAT Amount), `kurs` (Exchange Rate)
- **Faktur Info**: `faktur_no`, `faktur_date` (Tax Invoice Number & Date)
- **SAP Integration Fields**: `doc_num` (SAP Document Number), `posting_date`, `user_code`, `account`
- **Workflow**: `created_by`, `response_by`, `submit_at`, `response_at`, `status`
- **Type**: `type` (sales/purchase)

**Current Workflow**:
1. User creates faktur with customer, invoice details, DPP, PPN
2. Tax officer responds with `faktur_no` and `faktur_date`
3. Accounting officer updates `doc_num` and `posting_date` (manual entry)
4. No automatic SAP submission currently exists

### 2. Customer Model

**Structure**:
- `code`: Stores SAP Business Partner Code (BPCode)
- `name`: Customer name
- `project`: Associated project
- `type`: customer/vendor

**Key Finding**: Customer model already has SAP code mapping via `code` field, which should map to SAP B1 `CardCode`.

### 3. Existing SAP B1 Integration Patterns

**SapService** (`app/Services/SapService.php`):
- Cookie-based session management
- Automatic session expiration handling
- Generic `createJournalEntry()` method
- Pattern: `ensureSession()` → `handleSessionExpiration()` → API call

**SapJournalEntryBuilder** (`app/Services/SapJournalEntryBuilder.php`):
- Constructs journal entry payloads from local models
- Validates data before submission
- Maps local fields to SAP B1 format

**Controller Pattern** (`SapSyncController`):
- Permission checks
- Database transactions
- Error handling with rollback
- Audit logging via `SapSubmissionLog`

---

## Integration Concept

### 1. SAP B1 AR Invoice Service Layer Endpoint

**Endpoint**: `/Invoices` (SAP B1 Service Layer)

**HTTP Method**: `POST`

**Required Payload Structure**:
```json
{
  "CardCode": "C00001",
  "DocDate": "2025-01-15",
  "DocDueDate": "2025-01-30",
  "TaxDate": "2025-01-15",
  "DocCurrency": "IDR",
  "DocRate": 1.0,
  "DocumentLines": [
    {
      "ItemCode": "TAXABLE",
      "Quantity": 1,
      "UnitPrice": 1000000.0,
      "TaxCode": "VAT11",
      "LineTotal": 1000000.0
    },
    {
      "ItemCode": "VAT",
      "Quantity": 1,
      "UnitPrice": 110000.0,
      "TaxCode": "VAT11",
      "LineTotal": 110000.0
    }
  ],
  "Comments": "Invoice No: INV-001"
}
```

### 2. Integration Architecture

```
┌─────────────────┐
│  Faktur Model   │
│  (Sales Type)   │
└────────┬────────┘
         │
         │ 1. User completes faktur
         │    (faktur_no + faktur_date filled)
         ▼
┌─────────────────────────┐
│  VatController          │
│  sales_update()         │
│  OR                     │
│  New: submitToSap()     │
└────────┬────────────────┘
         │
         │ 2. Validate & Build Payload
         ▼
┌─────────────────────────┐
│  SapArInvoiceBuilder    │
│  - Maps customer.code    │
│  - Calculates line items │
│  - Formats dates         │
│  - Validates data        │
└────────┬────────────────┘
         │
         │ 3. Submit to SAP
         ▼
┌─────────────────────────┐
│  SapService              │
│  createArInvoice()      │
│  POST /Invoices          │
└────────┬────────────────┘
         │
         │ 4. SAP Response
         ▼
┌─────────────────────────┐
│  Update Faktur          │
│  - doc_num (DocNum)      │
│  - sap_submitted_at      │
│  - sap_submitted_by      │
│  - sap_submission_status │
└─────────────────────────┘
```

### 3. Data Mapping Strategy

| Local Field (Faktur) | SAP B1 Field | Mapping Logic |
|---------------------|--------------|---------------|
| `customer->code` | `CardCode` | Direct mapping (Business Partner Code) |
| `invoice_date` | `DocDate` | Format: `Y-m-d` |
| `invoice_date + 15 days` | `DocDueDate` | Default payment terms (configurable) |
| `invoice_date` | `TaxDate` | Same as DocDate |
| `kurs` | `DocRate` | Exchange rate (default 1.0 for IDR) |
| `dpp` | `DocumentLines[0].UnitPrice` | Taxable amount line |
| `ppn` | `DocumentLines[1].UnitPrice` | VAT amount line |
| `invoice_no` | `Comments` | Reference in comments |
| `faktur_no` | `NumAtCard` | Tax invoice number |
| `remarks` | `Comments` (append) | Additional notes |

**Line Items Structure**:
- **Line 1**: DPP (Tax Base) - ItemCode: "TAXABLE" or configurable
- **Line 2**: PPN (VAT) - ItemCode: "VAT" or configurable, TaxCode: "VAT11" (configurable)

### 4. Required Configuration

**Environment Variables** (add to `.env`):
```env
# SAP B1 AR Invoice Configuration
SAP_AR_INVOICE_ITEM_CODE_TAXABLE=TAXABLE
SAP_AR_INVOICE_ITEM_CODE_VAT=VAT
SAP_AR_INVOICE_TAX_CODE=VAT11
SAP_AR_INVOICE_DEFAULT_PAYMENT_TERMS=15
```

**Config File** (`config/services.php`):
```php
'sap' => [
    'server_url' => env('SAP_SERVER_URL'),
    'db_name' => env('SAP_DB_NAME'),
    'user' => env('SAP_USER'),
    'password' => env('SAP_PASSWORD'),
    'ar_invoice' => [
        'item_code_taxable' => env('SAP_AR_INVOICE_ITEM_CODE_TAXABLE', 'TAXABLE'),
        'item_code_vat' => env('SAP_AR_INVOICE_ITEM_CODE_VAT', 'VAT'),
        'tax_code' => env('SAP_AR_INVOICE_TAX_CODE', 'VAT11'),
        'default_payment_terms' => env('SAP_AR_INVOICE_DEFAULT_PAYMENT_TERMS', 15),
    ],
],
```

---

## Clarifications Needed

### 1. Customer SAP Code Mapping

**Question**: Is `Customer->code` field guaranteed to contain valid SAP B1 Business Partner Code (CardCode)?

**Recommendation**: 
- Add validation in `SapArInvoiceBuilder` to verify customer has valid SAP code
- Consider adding `sap_bp_code` field to customers table if `code` is not always SAP code
- Implement customer SAP code sync from SAP B1 Business Partners master data

### 2. Item Codes for Line Items

**Question**: What ItemCodes should be used for DPP and PPN lines in SAP B1?

**Options**:
- **Option A**: Use existing inventory items (e.g., "SERVICE", "GOODS")
- **Option B**: Use special item codes (e.g., "TAXABLE", "VAT") - requires setup in SAP B1
- **Option C**: Use GL Account codes directly (if SAP B1 allows account-based invoices)

**Recommendation**: Start with Option B (special item codes) as it's most flexible. These items should be configured in SAP B1 with:
- ItemCode: "TAXABLE" - Type: Service, GL Account: AR Account
- ItemCode: "VAT" - Type: Service, GL Account: VAT Output Account

### 3. Tax Code Configuration

**Question**: What TaxCode should be used? Is it always "VAT11" (11% VAT)?

**Recommendation**: 
- Make TaxCode configurable per faktur or customer
- Default to "VAT11" but allow override
- Consider adding `tax_code` field to fakturs table if multiple tax rates are needed

### 4. Payment Terms

**Question**: What should be the default payment terms (DocDueDate calculation)?

**Current**: Not specified in faktur model

**Recommendation**:
- Default: `invoice_date + 15 days` (configurable)
- Future: Add `payment_terms_days` field to customers table for customer-specific terms
- Allow manual override in UI before submission

### 5. Currency Handling

**Question**: How should foreign currency fakturs be handled?

**Current**: `kurs` field exists but not used in SAP integration

**Recommendation**:
- If `kurs != 1.0`, set `DocCurrency` to foreign currency (USD, AUD, SGD)
- Set `DocRate` to `kurs` value
- Ensure SAP B1 has currency setup for foreign currencies

### 6. Submission Trigger

**Question**: When should faktur be submitted to SAP B1?

**Options**:
- **Option A**: When `faktur_no` and `faktur_date` are filled (tax officer response)
- **Option B**: When `doc_num` is manually entered (current workflow)
- **Option C**: Separate "Submit to SAP" button (recommended)

**Recommendation**: Option C - Add explicit "Submit to SAP" button:
- Prevents accidental submissions
- Allows validation before submission
- Provides clear user feedback
- Matches existing journal entry pattern

### 7. Duplicate Prevention

**Question**: How to prevent duplicate SAP submissions?

**Recommendation**:
- Check if `doc_num` already exists → prevent resubmission
- Add `sap_submission_status` field: `pending`, `submitted`, `failed`
- Add `sap_submission_attempts` counter
- Store SAP response in `sap_submission_logs` table (similar to journal entries)

### 8. Error Handling

**Question**: What happens if SAP submission fails?

**Recommendation**:
- Database transaction rollback (no local updates)
- Store error in `sap_submission_logs`
- Display user-friendly error message
- Allow retry after fixing issues
- Log full SAP error response for debugging

---

## Implementation Recommendations

### Phase 1: Foundation (Week 1)

1. **Database Migration**:
   ```php
   // Add SAP submission tracking fields to fakturs table
   - sap_submission_status (enum: pending, submitted, failed)
   - sap_submission_attempts (integer, default 0)
   - sap_submission_error (text, nullable)
   - sap_submitted_at (timestamp, nullable)
   - sap_submitted_by (foreignId, nullable)
   - sap_doc_entry (string, nullable) // SAP DocEntry
   ```

2. **SapService Enhancement**:
   ```php
   // Add method to SapService
   public function createArInvoice(array $invoiceData): array
   {
       // Similar to createJournalEntry() but POST to /Invoices
       // Extract DocNum from response
       // Return success/error with DocNum
   }
   ```

3. **SapArInvoiceBuilder Service**:
   ```php
   // New service class
   class SapArInvoiceBuilder
   {
       public function __construct(Faktur $faktur)
       public function build(): array
       public function validate(): array
       // Maps faktur to SAP AR Invoice payload
   }
   ```

### Phase 2: Controller Integration (Week 2)

1. **VatController Enhancement**:
   ```php
   // Add new method
   public function submitToSap(Request $request, Faktur $faktur)
   {
       // Permission check (similar to journal entry)
       // Validation (faktur_no, faktur_date, customer SAP code)
       // Build payload via SapArInvoiceBuilder
       // Submit via SapService
       // Update faktur with SAP doc_num
       // Log submission
   }
   ```

2. **Route Addition**:
   ```php
   // routes/accounting.php
   Route::post('/vat/fakturs/{faktur}/submit-to-sap', 
       [VatController::class, 'submitToSap'])
       ->name('accounting.vat.submit-to-sap')
       ->middleware('can:submit-sap-ar-invoice');
   ```

### Phase 3: UI Enhancement (Week 2-3)

1. **VAT Sales Complete Page**:
   - Add "Submit to SAP" button (only when `doc_num` is empty)
   - Show submission status badge
   - Display previous submission attempts
   - Confirmation modal (similar to journal entry)

2. **Submission Modal**:
   - Show faktur summary
   - Display customer SAP code
   - Show calculated line items (DPP + PPN)
   - Warning about posting implications
   - Previous submission attempts (if any)

### Phase 4: Testing & Refinement (Week 3-4)

1. **Unit Tests**:
   - SapArInvoiceBuilder payload construction
   - Data validation logic
   - Error handling scenarios

2. **Integration Tests**:
   - End-to-end submission flow
   - SAP error handling
   - Duplicate prevention
   - Transaction rollback on failure

3. **Browser Testing**:
   - Submit faktur to SAP B1
   - Verify doc_num update
   - Test error scenarios
   - Verify audit logging

---

## Technical Implementation Details

### 1. SapArInvoiceBuilder Class

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

        $invoice = [
            'CardCode' => $customer->code, // SAP Business Partner Code
            'DocDate' => $invoiceDate->format('Y-m-d'),
            'DocDueDate' => $dueDate->format('Y-m-d'),
            'TaxDate' => $invoiceDate->format('Y-m-d'),
            'DocCurrency' => $this->getCurrency(),
            'DocRate' => $this->faktur->kurs ?? 1.0,
            'NumAtCard' => $this->faktur->faktur_no,
            'Comments' => $this->buildComments(),
            'DocumentLines' => $this->buildDocumentLines(),
        ];

        return $invoice;
    }

    protected function buildDocumentLines(): array
    {
        $lines = [];

        // Line 1: DPP (Tax Base)
        $lines[] = [
            'ItemCode' => $this->config['item_code_taxable'],
            'Quantity' => 1,
            'UnitPrice' => (float) $this->faktur->dpp,
            'TaxCode' => $this->config['tax_code'],
            'LineTotal' => (float) $this->faktur->dpp,
        ];

        // Line 2: PPN (VAT)
        if ($this->faktur->ppn > 0) {
            $lines[] = [
                'ItemCode' => $this->config['item_code_vat'],
                'Quantity' => 1,
                'UnitPrice' => (float) $this->faktur->ppn,
                'TaxCode' => $this->config['tax_code'],
                'LineTotal' => (float) $this->faktur->ppn,
            ];
        }

        return $lines;
    }

    protected function getCurrency(): string
    {
        // Default to IDR, can be enhanced based on kurs value
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

        if ($this->faktur->doc_num) {
            $errors[] = 'Faktur already has SAP document number. Cannot resubmit.';
        }

        return $errors;
    }
}
```

### 2. SapService Enhancement

```php
// Add to app/Services/SapService.php

public function createArInvoice(array $invoiceData): array
{
    $this->ensureSession();

    return $this->handleSessionExpiration(function () use ($invoiceData) {
        try {
            Log::debug('SAP B1 AR Invoice Request', ['payload' => $invoiceData]);
            
            $response = $this->client->post('Invoices', [
                'json' => $invoiceData,
            ]);

            $statusCode = $response->getStatusCode();
            $body = json_decode($response->getBody()->getContents(), true);

            if ($statusCode === 201) {
                Log::info('SAP B1 AR Invoice created successfully', [
                    'doc_entry' => $body['DocEntry'] ?? null,
                    'doc_num' => $body['DocNum'] ?? null,
                ]);

                return [
                    'success' => true,
                    'doc_entry' => $body['DocEntry'] ?? null,
                    'doc_num' => $this->extractDocumentNumber($body),
                    'data' => $body,
                ];
            }

            $errorMessage = $body['error']['message']['value'] ?? 'Unknown error';
            throw new \Exception('Failed to create AR Invoice. Status: ' . $statusCode . '. Error: ' . $errorMessage);
        } catch (RequestException $e) {
            // Error handling similar to createJournalEntry()
            $errorMessage = 'Unknown error';
            if ($e->getResponse()) {
                $errorBody = json_decode($e->getResponse()->getBody()->getContents(), true);
                if (isset($errorBody['error']['message']['value'])) {
                    $errorMessage = $errorBody['error']['message']['value'];
                }
            }
            throw new \Exception('SAP B1 Error: ' . $errorMessage, 0, $e);
        }
    });
}

protected function extractDocumentNumber(array $response): ?string
{
    // Priority: DocNum is the SAP Document Number
    if (isset($response['DocNum'])) {
        return (string) $response['DocNum'];
    }

    if (isset($response['Invoice']['DocNum'])) {
        return (string) $response['Invoice']['DocNum'];
    }

    // Fallback to DocEntry
    if (isset($response['DocEntry'])) {
        return (string) $response['DocEntry'];
    }

    return null;
}
```

### 3. Database Migration

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fakturs', function (Blueprint $table) {
            $table->enum('sap_submission_status', ['pending', 'submitted', 'failed'])
                ->nullable()
                ->after('status');
            $table->integer('sap_submission_attempts')->default(0)->after('sap_submission_status');
            $table->text('sap_submission_error')->nullable()->after('sap_submission_attempts');
            $table->timestamp('sap_submitted_at')->nullable()->after('sap_submission_error');
            $table->foreignId('sap_submitted_by')->nullable()->after('sap_submitted_at');
            $table->string('sap_doc_entry')->nullable()->after('sap_submitted_by');
        });
    }

    public function down(): void
    {
        Schema::table('fakturs', function (Blueprint $table) {
            $table->dropColumn([
                'sap_submission_status',
                'sap_submission_attempts',
                'sap_submission_error',
                'sap_submitted_at',
                'sap_submitted_by',
                'sap_doc_entry',
            ]);
        });
    }
};
```

---

## Risk Assessment & Mitigation

### Risk 1: Customer SAP Code Missing or Invalid

**Impact**: High - Submission will fail

**Mitigation**:
- Validate customer SAP code before allowing submission
- Add UI warning if customer doesn't have SAP code
- Implement customer SAP code sync from SAP B1 Business Partners

### Risk 2: Item Codes Not Configured in SAP B1

**Impact**: High - Submission will fail

**Mitigation**:
- Document required SAP B1 item setup
- Add validation to check item codes exist (if SAP API supports)
- Provide clear error messages with setup instructions

### Risk 3: Duplicate Submissions

**Impact**: Medium - Creates duplicate invoices in SAP B1

**Mitigation**:
- Check `doc_num` before submission
- Add `sap_submission_status` to prevent resubmission
- Implement idempotency check (check SAP for existing invoice)

### Risk 4: Currency Mismatch

**Impact**: Medium - Incorrect amounts in SAP B1

**Mitigation**:
- Validate currency configuration
- Default to IDR if kurs = 1.0
- Add currency field to fakturs table if needed

### Risk 5: Tax Code Configuration

**Impact**: Medium - Incorrect tax calculation

**Mitigation**:
- Make tax code configurable
- Default to VAT11 (11% VAT)
- Allow per-customer or per-faktur tax code override

---

## Success Criteria

1. ✅ Faktur can be submitted to SAP B1 AR Invoice successfully
2. ✅ SAP Document Number (`DocNum`) is stored in `fakturs.doc_num`
3. ✅ Submission status is tracked and displayed in UI
4. ✅ Errors are handled gracefully with rollback
5. ✅ Audit trail is maintained via `sap_submission_logs`
6. ✅ Duplicate submissions are prevented
7. ✅ User receives clear feedback on submission status

---

## Next Steps

1. **Review & Approval**: Review this document with stakeholders
2. **Clarify Questions**: Address all clarification questions above
3. **SAP B1 Setup**: Configure required items and tax codes in SAP B1
4. **Customer SAP Code Sync**: Implement Business Partners sync if needed
5. **Implementation**: Follow phased implementation plan
6. **Testing**: Comprehensive testing in development environment
7. **Documentation**: Update user guides and technical documentation

---

## References

- SAP B1 Service Layer API Documentation: `/Invoices` endpoint
- Existing Integration: `app/Services/SapService.php` (Journal Entry pattern)
- Existing Builder: `app/Services/SapJournalEntryBuilder.php` (Pattern to follow)
- Database Schema: `database/migrations/2024_10_16_161115_create_fakturs_table.php`

---

**Document Version**: 1.0  
**Last Updated**: 2025-01-XX  
**Author**: AI Assistant  
**Status**: Draft - Pending Review
