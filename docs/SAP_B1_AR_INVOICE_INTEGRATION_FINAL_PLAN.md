# SAP B1 AR Invoice + Journal Entry Integration - Final Implementation Plan

## Executive Summary

This document provides the **finalized implementation plan** for integrating Faktur (Sales Invoice) module with SAP B1, creating both AR Invoice and Journal Entry documents automatically. All clarifications have been addressed and implementation approach is confirmed.

---

## Confirmed Requirements

### 1. AR Invoice Structure

✅ **G/L Account-based lines** (not item-based)

-   Uses Account Code `11401039` (Piutang Usaha Belum Ditagih) directly

### 2. Account Configuration

✅ **Revenue Account**: Selectable between:

-   `41101` - Pendapatan Kontrak (Default)
-   `41201` - Pendapatan Rental Equipment

✅ **AR Account**: Fixed at `11401039` (Piutang Usaha Belum Ditagih)

### 3. Project & Department Mapping

✅ **Project**: Use `customer->project` field
✅ **Department**: Use default department from customer (Option B)

### 4. Amount Calculation

✅ **Journal Entry**: Use DPP amount only (not total with tax)
✅ **AR Invoice**: Use DPP + PPN - WTax (if WTax exists)

### 5. WTax Handling

✅ **AR Invoice**: Include WTax if faktur has WTax data
✅ **Journal Entry**: Do NOT include WTax line

### 6. Transaction Strategy

✅ **Option B**: Create AR Invoice first, then Journal Entry

-   If JE fails, mark as partial completion (`ar_created`)
-   Store AR Invoice DocNum even if JE fails
-   Allow manual JE creation later if needed

---

## Database Schema Changes

### Migration 1: Add SAP Tracking Fields to Fakturs Table

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
            // Revenue account selection
            $table->string('revenue_account_code', 20)->nullable()->after('account');

            // SAP AR Invoice tracking
            $table->string('sap_ar_doc_num')->nullable()->after('doc_num');
            $table->string('sap_ar_doc_entry')->nullable()->after('sap_ar_doc_num');

            // SAP Journal Entry tracking
            $table->string('sap_je_num')->nullable()->after('sap_ar_doc_entry');
            $table->string('sap_je_doc_entry')->nullable()->after('sap_je_num');

            // Submission tracking
            $table->enum('sap_submission_status', ['pending', 'ar_created', 'je_created', 'completed', 'failed'])
                ->nullable()->after('status');
            $table->integer('sap_submission_attempts')->default(0)->after('sap_submission_status');
            $table->text('sap_submission_error')->nullable()->after('sap_submission_attempts');
            $table->timestamp('sap_submitted_at')->nullable()->after('sap_submission_error');
            $table->foreignId('sap_submitted_by')->nullable()->after('sap_submitted_at');

            // Project for JE (Department comes from customer default)
            $table->string('project', 10)->nullable()->after('customer_id');

            // WTax field (if not exists)
            if (!Schema::hasColumn('fakturs', 'wtax_amount')) {
                $table->decimal('wtax_amount', 20, 2)->nullable()->after('ppn');
            }
        });
    }

    public function down(): void
    {
        Schema::table('fakturs', function (Blueprint $table) {
            $table->dropColumn([
                'revenue_account_code',
                'sap_ar_doc_num',
                'sap_ar_doc_entry',
                'sap_je_num',
                'sap_je_doc_entry',
                'sap_submission_status',
                'sap_submission_attempts',
                'sap_submission_error',
                'sap_submitted_at',
                'sap_submitted_by',
                'project',
            ]);

            if (Schema::hasColumn('fakturs', 'wtax_amount')) {
                $table->dropColumn('wtax_amount');
            }
        });
    }
};
```

### Migration 2: Add Default Department Code to Customers Table

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // Default department code for Journal Entry (Option B)
            $table->string('default_department_code', 10)->nullable()->after('project');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('default_department_code');
        });
    }
};
```

### Migration 3: Add Revenue Account Selection to Fakturs Form

**Note**: This will be handled in the UI/form, not database migration. The `revenue_account_code` field is already added in Migration 1.

---

## Service Layer Implementation

### 1. SapArInvoiceBuilder Service

**File**: `app/Services/SapArInvoiceBuilder.php`

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

        // G/L Account-based structure
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
        // Fixed AR Account: 11401039
        $arAccountCode = $this->config['default_ar_account'];

        // Calculate total: DPP + PPN - WTax
        $totalAmount = (float) $this->faktur->dpp + (float) ($this->faktur->ppn ?? 0);
        if ($this->faktur->wtax_amount && $this->faktur->wtax_amount > 0) {
            $totalAmount -= (float) $this->faktur->wtax_amount;
        }

        $line = [
            'AccountCode' => $arAccountCode, // 11401039
            'LineTotal' => $totalAmount,
        ];

        // Add Project if available
        if ($this->faktur->project) {
            $line['ProjectCode'] = $this->faktur->project;
        }

        // Add Department/Cost Center from customer default (Option B)
        $customer = $this->faktur->customer;
        if ($customer && $customer->default_department_code) {
            $line['CostingCode'] = $customer->default_department_code;
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
        $revenueAccount = $this->faktur->revenue_account_code ?? $this->config['default_revenue_account'];
        if (!in_array($revenueAccount, $validRevenueAccounts)) {
            $errors[] = 'Invalid revenue account code. Must be 41101 or 41201.';
        }

        return $errors;
    }
}
```

### 2. SapArInvoiceJeBuilder Service

**File**: `app/Services/SapArInvoiceJeBuilder.php`

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

        // Revenue account: selectable (41101 or 41201)
        $revenueAccountCode = $this->faktur->revenue_account_code ?? $this->config['default_revenue_account'];

        // AR Account: fixed at 11401039
        $arAccountCode = $this->config['default_ar_account'];

        // Use DPP amount only (not total with tax)
        $dppAmount = (float) $this->faktur->dpp;

        // Get project from customer or faktur
        $projectCode = $this->faktur->project ?? $customer->project;

        // Get department from customer default (Option B)
        $departmentCode = $this->getDepartmentCode();

        $journalEntry = [
            'ReferenceDate' => $invoiceDate->format('Y-m-d'),
            'TaxDate' => $invoiceDate->format('Y-m-d'),
            'DueDate' => $invoiceDate->format('Y-m-d'),
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
                    'AccountCode' => $arAccountCode, // 11401039 (fixed)
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

        // Fallback: If customer doesn't have default department, return null
        // (SAP B1 may allow null department)
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

        // Validate revenue account code
        $revenueAccount = $this->faktur->revenue_account_code ?? $this->config['default_revenue_account'];
        if (!in_array($revenueAccount, ['41101', '41201'])) {
            $errors[] = 'Invalid revenue account code. Must be 41101 or 41201.';
        }

        return $errors;
    }
}
```

### 3. SapService Enhancement

**File**: `app/Services/SapService.php` (add method)

```php
// Add to existing SapService class

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
                    'doc_num' => $this->extractArInvoiceNumber($body),
                    'data' => $body,
                ];
            }

            $errorMessage = $body['error']['message']['value'] ?? 'Unknown error';
            throw new \Exception('Failed to create AR Invoice. Status: ' . $statusCode . '. Error: ' . $errorMessage);
        } catch (RequestException $e) {
            $errorMessage = 'Unknown error';
            if ($e->getResponse()) {
                $errorBody = json_decode($e->getResponse()->getBody()->getContents(), true);
                if (isset($errorBody['error']['message']['value'])) {
                    $errorMessage = $errorBody['error']['message']['value'];
                } elseif (isset($errorBody['error']['message'])) {
                    $errorMessage = is_array($errorBody['error']['message'])
                        ? ($errorBody['error']['message']['value'] ?? json_encode($errorBody['error']['message']))
                        : $errorBody['error']['message'];
                }
            }
            throw new \Exception('SAP B1 Error: ' . $errorMessage, 0, $e);
        }
    });
}

protected function extractArInvoiceNumber(array $response): ?string
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

---

## Controller Implementation

### VatController Enhancement

**File**: `app/Http/Controllers/Accounting/VatController.php` (add method)

```php
// Add to existing VatController class

use App\Services\SapService;
use App\Services\SapArInvoiceBuilder;
use App\Services\SapArInvoiceJeBuilder;
use App\Models\SapSubmissionLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

public function submitToSap(Request $request, Faktur $faktur)
{
    // Permission check
    if (!auth()->user()->can('submit-sap-ar-invoice')) {
        abort(403, 'Unauthorized action.');
    }

    // Validate revenue account code if provided
    if ($request->has('revenue_account_code')) {
        $validAccounts = ['41101', '41201'];
        if (!in_array($request->revenue_account_code, $validAccounts)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid revenue account code. Must be 41101 or 41201.',
            ], 422);
        }
        $faktur->revenue_account_code = $request->revenue_account_code;
    }

    // Set default revenue account if not set
    if (empty($faktur->revenue_account_code)) {
        $faktur->revenue_account_code = config('services.sap.ar_invoice.default_revenue_account');
    }

    // Set project from customer if not set
    if (empty($faktur->project)) {
        $faktur->project = $faktur->customer->project;
    }

            // Department will be taken from customer's default_department_code (Option B)
            // No need to set department_id on faktur

    $faktur->save();

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
        $faktur->sap_submission_attempts = ($faktur->sap_submission_attempts ?? 0) + 1;
        $faktur->save();

        // Log AR Invoice creation
        SapSubmissionLog::create([
            'faktur_id' => $faktur->id,
            'document_type' => 'ar_invoice',
            'status' => 'success',
            'sap_doc_num' => $arResult['doc_num'],
            'sap_response' => json_encode($arResult['data']),
            'attempt_number' => $faktur->sap_submission_attempts,
            'submitted_by' => auth()->id(),
        ]);

        // Step 4: Build and validate Journal Entry
        $jeBuilder = new SapArInvoiceJeBuilder($faktur);
        $jeErrors = $jeBuilder->validate();

        if (!empty($jeErrors)) {
            // AR Invoice created but JE validation failed - mark as partial
            $faktur->sap_submission_status = 'ar_created';
            $faktur->sap_submission_error = 'JE Validation failed: ' . implode(', ', $jeErrors);
            $faktur->save();

            DB::commit();

            return response()->json([
                'success' => false,
                'partial' => true,
                'message' => 'AR Invoice created successfully, but Journal Entry validation failed: ' . implode(', ', $jeErrors),
                'ar_doc_num' => $arResult['doc_num'],
            ], 422);
        }

        $jeData = $jeBuilder->build();

        // Step 5: Create Journal Entry in SAP B1
        $jeResult = $sapService->createJournalEntry($jeData);

        if (!($jeResult['success'] ?? false)) {
            // AR Invoice created but JE creation failed - mark as partial
            $faktur->sap_submission_status = 'ar_created';
            $faktur->sap_submission_error = 'JE Creation failed: ' . ($jeResult['message'] ?? 'Unknown error');
            $faktur->save();

            // Log JE failure
            SapSubmissionLog::create([
                'faktur_id' => $faktur->id,
                'document_type' => 'journal_entry',
                'status' => 'failed',
                'sap_error' => $jeResult['message'] ?? 'Unknown error',
                'attempt_number' => $faktur->sap_submission_attempts,
                'submitted_by' => auth()->id(),
            ]);

            DB::commit();

            return response()->json([
                'success' => false,
                'partial' => true,
                'message' => 'AR Invoice created successfully, but Journal Entry creation failed: ' . ($jeResult['message'] ?? 'Unknown error'),
                'ar_doc_num' => $arResult['doc_num'],
            ], 422);
        }

        // Step 6: Update faktur with Journal Entry info
        $faktur->sap_je_num = $jeResult['journal_number'];
        $faktur->sap_je_doc_entry = $jeResult['doc_entry'];
        $faktur->sap_submission_status = 'completed';
        $faktur->sap_submitted_at = now();
        $faktur->sap_submitted_by = auth()->id();
        $faktur->sap_submission_error = null;
        $faktur->save();

        // Log Journal Entry creation
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
            'trace' => $e->getTraceAsString(),
        ]);

        // Update faktur with error
        $faktur->sap_submission_status = 'failed';
        $faktur->sap_submission_error = $e->getMessage();
        $faktur->sap_submission_attempts = ($faktur->sap_submission_attempts ?? 0) + 1;
        $faktur->save();

        // Log failure
        SapSubmissionLog::create([
            'faktur_id' => $faktur->id,
            'document_type' => 'ar_invoice',
            'status' => 'failed',
            'sap_error' => $e->getMessage(),
            'attempt_number' => $faktur->sap_submission_attempts,
            'submitted_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to submit to SAP B1: ' . $e->getMessage(),
        ], 500);
    }
}
```

---

## Route Addition

**File**: `routes/accounting.php`

```php
// Add route for SAP submission
Route::post('/vat/fakturs/{faktur}/submit-to-sap',
    [VatController::class, 'submitToSap'])
    ->name('accounting.vat.submit-to-sap')
    ->middleware('can:submit-sap-ar-invoice');
```

---

## Configuration

### Environment Variables

```env
# SAP B1 AR Invoice Configuration
SAP_AR_INVOICE_DEFAULT_PAYMENT_TERMS=15
SAP_AR_INVOICE_DEFAULT_REVENUE_ACCOUNT=41101
SAP_AR_INVOICE_DEFAULT_AR_ACCOUNT=11401039
```

### Config File

**File**: `config/services.php` (add to existing 'sap' array)

```php
'sap' => [
    // ... existing config ...
    'ar_invoice' => [
        'default_payment_terms' => env('SAP_AR_INVOICE_DEFAULT_PAYMENT_TERMS', 15),
        'default_revenue_account' => env('SAP_AR_INVOICE_DEFAULT_REVENUE_ACCOUNT', '41101'),
        'default_ar_account' => env('SAP_AR_INVOICE_DEFAULT_AR_ACCOUNT', '11401039'),
    ],
],
```

---

## UI Implementation

### 1. Add Revenue Account Selection to Faktur Form

**File**: `resources/views/user-payreqs/fakturs/index.blade.php` (or create form)

Add dropdown in faktur creation/edit form:

```blade
<div class="form-group">
    <label for="revenue_account_code">Revenue Account <span class="text-danger">*</span></label>
    <select name="revenue_account_code" id="revenue_account_code" class="form-control" required>
        <option value="41101" {{ old('revenue_account_code', $faktur->revenue_account_code ?? '41101') == '41101' ? 'selected' : '' }}>
            41101 - Pendapatan Kontrak (Default)
        </option>
        <option value="41201" {{ old('revenue_account_code', $faktur->revenue_account_code ?? '41101') == '41201' ? 'selected' : '' }}>
            41201 - Pendapatan Rental Equipment
        </option>
    </select>
    <small class="form-text text-muted">Select revenue account for journal entry</small>
</div>
```

### 2. Add Submit to SAP Button

**File**: `resources/views/accounting/vat/ar/complete.blade.php` (or appropriate view)

Add button in action column or above table:

```blade
@if(empty($faktur->sap_ar_doc_num) && $faktur->faktur_no && $faktur->faktur_date)
    <button type="button"
            class="btn btn-sm btn-primary submit-to-sap-btn"
            data-faktur-id="{{ $faktur->id }}"
            data-faktur-no="{{ $faktur->invoice_no }}">
        <i class="fas fa-paper-plane"></i> Submit to SAP
    </button>
@elseif($faktur->sap_ar_doc_num)
    <span class="badge badge-success">
        <i class="fas fa-check"></i> SAP: {{ $faktur->sap_ar_doc_num }}
        @if($faktur->sap_je_num)
            | JE: {{ $faktur->sap_je_num }}
        @endif
    </span>
@endif
```

### 3. Add JavaScript for Submission

**File**: `resources/views/accounting/vat/ar/complete.blade.php` (add script section)

```javascript
<script>
$(document).ready(function() {
    $('.submit-to-sap-btn').on('click', function() {
        const fakturId = $(this).data('faktur-id');
        const fakturNo = $(this).data('faktur-no');
        const btn = $(this);

        // Show confirmation modal
        Swal.fire({
            title: 'Submit to SAP B1?',
            html: `
                <div class="text-left">
                    <p><strong>Invoice No:</strong> ${fakturNo}</p>
                    <p>This will create:</p>
                    <ul>
                        <li>AR Invoice in SAP B1</li>
                        <li>Journal Entry (Revenue + AR)</li>
                    </ul>
                    <p class="text-warning"><i class="fas fa-exclamation-triangle"></i> This action cannot be undone.</p>
                </div>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, Submit',
            cancelButtonText: 'Cancel',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return fetch(`{{ route('accounting.vat.submit-to-sap', '') }}/${fakturId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    body: JSON.stringify({})
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        throw new Error(data.message || 'Submission failed');
                    }
                    return data;
                })
                .catch(error => {
                    Swal.showValidationMessage(`Request failed: ${error.message}`);
                });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Success!',
                    html: `
                        <p>AR Invoice and Journal Entry created successfully.</p>
                        <p><strong>AR Doc:</strong> ${result.value.ar_doc_num}</p>
                        <p><strong>JE Num:</strong> ${result.value.je_num}</p>
                    `,
                    icon: 'success',
                    confirmButtonText: 'OK'
                }).then(() => {
                    location.reload();
                });
            }
        });
    });
});
</script>
```

---

## Permission Setup

**File**: `database/seeders/PermissionSeeder.php` (or create permission)

```php
// Add permission for SAP AR Invoice submission
Permission::create(['name' => 'submit-sap-ar-invoice']);
```

Assign to appropriate roles (admin, accounting, etc.)

---

## SapSubmissionLog Model Enhancement

**File**: `app/Models/SapSubmissionLog.php` (update if exists, or create)

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SapSubmissionLog extends Model
{
    protected $fillable = [
        'faktur_id',
        'document_type', // 'ar_invoice' or 'journal_entry'
        'status', // 'success' or 'failed'
        'sap_doc_num',
        'sap_doc_entry',
        'sap_response',
        'sap_error',
        'attempt_number',
        'submitted_by',
    ];

    protected $casts = [
        'sap_response' => 'array',
    ];

    public function faktur()
    {
        return $this->belongsTo(Faktur::class);
    }

    public function submittedBy()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }
}
```

**Migration**: Create `sap_submission_logs` table (if not exists from journal entry integration)

---

## Testing Checklist

### Unit Tests

-   [ ] SapArInvoiceBuilder validates correctly
-   [ ] SapArInvoiceJeBuilder validates correctly
-   [ ] Revenue account selection (41101/41201) works
-   [ ] WTax included in AR Invoice but not in JE
-   [ ] DPP amount used for JE (not total)

### Integration Tests

-   [ ] AR Invoice creation succeeds
-   [ ] Journal Entry creation succeeds
-   [ ] Partial completion (AR only) handled correctly
-   [ ] Error handling and rollback works
-   [ ] Audit logging works

### Browser Tests

-   [ ] Submit button appears when faktur ready
-   [ ] Confirmation modal shows correct info
-   [ ] Success message displays AR Doc and JE Num
-   [ ] Status badges update correctly
-   [ ] Revenue account dropdown works

---

## Implementation Timeline

### Week 1: Foundation

-   Day 1-2: Database migrations
-   Day 3-4: Service layer (Builders + SapService)
-   Day 5: Configuration setup

### Week 2: Controller & Routes

-   Day 1-2: Controller implementation
-   Day 3: Routes and permissions
-   Day 4-5: Testing service layer

### Week 3: UI & Integration

-   Day 1-2: Form updates (revenue account selection)
-   Day 3-4: Submit button and JavaScript
-   Day 5: Integration testing

### Week 4: Testing & Refinement

-   Day 1-3: Comprehensive testing
-   Day 4: Bug fixes
-   Day 5: Documentation update

---

## Success Criteria

✅ AR Invoice created in SAP B1 with correct data
✅ Journal Entry created with DPP amount only
✅ Revenue account selectable (41101 or 41201)
✅ AR Account fixed at 11401039
✅ Project and Department correctly mapped
✅ WTax included in AR Invoice but not in JE
✅ Partial completion handled (AR created, JE failed)
✅ Audit trail maintained
✅ Error handling works correctly
✅ UI provides clear feedback

---

**Document Version**: 1.0 Final  
**Last Updated**: 2025-01-XX  
**Status**: Ready for Implementation
