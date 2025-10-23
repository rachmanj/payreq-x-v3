# Loans & Bilyets Integration Documentation

## Overview

Comprehensive integration between Loans and Bilyets modules to support multiple payment methods including bilyet payments, auto-debit, cash, transfers, and other payment types.

## Database Schema Changes

### New Migrations Created

1. **`add_bilyet_id_and_payment_method_to_installments_table.php`**

    - Adds `bilyet_id` (nullable FK to bilyets table)
    - Adds `payment_method` enum: ['bilyet', 'auto_debit', 'cash', 'transfer', 'other']
    - Indexes on both fields for performance

2. **`add_purpose_to_bilyets_table.php`**

    - Adds `purpose` enum: ['loan_payment', 'operational', 'other']
    - Default: 'operational'
    - Index for filtering

3. **`create_loan_audits_table.php`**
    - Full audit trail for loans (similar to bilyet_audits)
    - Tracks: create, update, delete, status changes

### Relationship Diagram

```
Loan (1) ─────── (N) Installment (N) ─────── (1) Bilyet
                         │
                         └─ payment_method: determines HOW paid
                         └─ bilyet_id: links to actual bilyet (if payment_method='bilyet')

Bilyet
  └─ purpose: 'loan_payment' | 'operational' | 'other'
  └─ loan_id: optional link to loan
```

## Business Rules

### Payment Methods

1. **Bilyet Payment** (`payment_method='bilyet'`)

    - Requires `bilyet_id` to be set
    - Creates actual Bilyet record
    - Links installment to bilyet
    - Bilyet must have `purpose='loan_payment'`

2. **Auto-Debit Payment** (`payment_method='auto_debit'`)

    - NO bilyet created
    - Direct bank account debit
    - `bilyet_id` must be NULL
    - Only requires `paid_date`

3. **Other Methods** (`cash`, `transfer`, `other`)
    - NO bilyet created
    - Manual payment tracking
    - `bilyet_id` must be NULL

### Validation Rules

-   If `payment_method='bilyet'`, must have `bilyet_id`
-   If `payment_method='auto_debit'`, `bilyet_id` must be NULL
-   Bilyet amount should be >= installment amount (1% tolerance)
-   Bilyet bank account should match installment account (recommended)
-   Cannot link voided bilyet to installment

## New Features Implemented

### Phase 1: Database & Models ✅

-   [x] Migrations for bilyet_id, payment_method, purpose
-   [x] Updated Installment model with:
    -   Payment method constants
    -   Bilyet relationship
    -   Scopes: paid(), unpaid(), byPaymentMethod()
    -   Accessor: payment_method_label
-   [x] Updated Bilyet model with:
    -   Purpose constants
    -   Installment relationship
    -   Scopes: byPurpose(), loanPayments(), operational()
    -   Accessor: purpose_label
    -   Helper: isForLoanPayment()

### Phase 2: Loan Audit Trail ✅

-   [x] LoanAudit model
-   [x] Events: LoanCreated, LoanUpdated, LoanStatusChanged
-   [x] Listener: LogLoanAudit
-   [x] Integrated into LoanController (store, update methods)
-   [x] Event registration in EventServiceProvider

### Phase 3: Service Layer ✅

-   [x] **LoanPaymentService**:

    -   `validateBilyetForInstallment()`: Validates bilyet can be linked
    -   `linkBilyetToInstallment()`: Creates link with validation
    -   `unlinkBilyet()`: Removes link
    -   `markInstallmentAsPaid()`: Generic payment marking

-   [x] **InstallmentPaymentService**:
    -   `createBilyetAndPay()`: Creates bilyet and links to installment
    -   `markAsPaid()`: Marks as paid with optional bilyet
    -   `markAsAutoDebitPaid()`: Marks as auto-debit payment

### Phase 4: Controller Updates ✅

-   [x] **InstallmentController**:

    -   Added dependency injection for InstallmentPaymentService
    -   New method: `createBilyetForPayment()` - Create bilyet from installment
    -   New method: `markAsAutoDebitPaid()` - Mark as auto-debit paid
    -   Updated `data()` method to include payment_method column

-   [x] **LoanController**:
    -   Added audit event triggers in store/update methods
    -   Status change detection

### Phase 5: Routes ✅

-   [x] `POST /accounting/loans/installments/{id}/create-bilyet`
-   [x] `POST /accounting/loans/installments/{id}/mark-auto-debit`

### Phase 6: Views & UI ✅

-   [x] **Updated `accounting/loans/installments/action.blade.php`**:

    -   "Create Bilyet" button (for unpaid installments)
    -   "Mark as Auto-Debit" button (for unpaid installments)
    -   Modal: Create Bilyet for Payment (full form with pre-filled data)
    -   Modal: Mark as Auto-Debit Paid (simple paid date selection)

-   [x] **Updated `accounting/loans/show.blade.php`**:
    -   Added "Payment Method" column to installments table
    -   Added Select2 support for modals
    -   Updated DataTables configuration

## User Workflows

### Workflow 1: Pay Installment via Bilyet

1. Navigate to Loan details
2. Find unpaid installment
3. Click "Bilyet" button
4. Fill bilyet details (number, bank account, type, date, amount)
5. Submit → Creates bilyet with `purpose='loan_payment'` and links to installment
6. Installment now shows `payment_method='bilyet'`

### Workflow 2: Pay Installment via Auto-Debit

1. Navigate to Loan details
2. Find unpaid installment
3. Click "Auto-Debit" button
4. Select paid date
5. Submit → Marks installment as paid
6. Installment shows `payment_method='auto_debit'`, NO bilyet created

### Workflow 3: View Payment Method

-   In installments table, "Payment Method" column shows:
    -   "Bilyet Payment" (with icon/badge)
    -   "Auto Debit" (with icon/badge)
    -   "Cash", "Bank Transfer", "Other"
    -   "-" (if unpaid)

## API Examples

### Create Bilyet from Installment

```php
POST /accounting/loans/installments/123/create-bilyet

{
    "giro_id": 5,
    "prefix": "BCA",
    "nomor": "1234567",
    "type": "bg",
    "bilyet_date": "2025-11-01",
    "amount": 50000000,
    "remarks": "Loan payment for installment #1"
}
```

### Mark as Auto-Debit Paid

```php
POST /accounting/loans/installments/123/mark-auto-debit

{
    "paid_date": "2025-10-25"
}
```

## Next Steps (Future Implementation)

### Phase 7: Advanced UI Features (Planned)

-   Loan dashboard with payment statistics
-   Advanced filtering on loans index
-   Bulk operations for loans
-   Loan history page (similar to bilyet history)

### Phase 8: Enhanced Reporting (Planned)

-   Payment method breakdown in reports
-   Loan payment details report
-   Bilyet by purpose report
-   Cash flow analysis

### Phase 9: Bilyet Module Updates (Planned)

-   Add "Purpose" field to bilyet create/edit forms
-   Filter bilyets by purpose
-   Show linked installment in bilyet details
-   Auto-suggest installments when creating loan payment bilyet

## Migration Notes

### For Existing Data

-   All existing installments will have `payment_method=NULL` and `bilyet_id=NULL`
-   All existing bilyets will have `purpose='operational'` (default)
-   Update existing data manually or via data migration script as needed

### Running Migrations

```bash
php artisan migrate
```

**Note**: Migrations are currently pending. Run when database is accessible.

## Testing Checklist

-   [ ] Create loan with installments
-   [ ] Create bilyet payment from installment
-   [ ] Mark installment as auto-debit paid
-   [ ] Verify payment method displays correctly
-   [ ] Edit installment after payment
-   [ ] Delete unpaid installment
-   [ ] Verify audit trail for loan changes
-   [ ] Test validation (e.g., can't link voided bilyet)

## Technical Notes

### Performance Considerations

-   Indexes on `bilyet_id` and `payment_method` for fast queries
-   Use eager loading when fetching installments with bilyets:
    ```php
    $installments = Installment::with('bilyet', 'loan')->get();
    ```

### Error Handling

All service methods use try-catch with DB transactions:

```php
try {
    DB::beginTransaction();
    // operations
    DB::commit();
} catch (Exception $e) {
    DB::rollBack();
    throw $e;
}
```

### Security

-   All routes protected by authentication middleware
-   Role-based access control via `@hasanyrole` directives
-   CSRF protection on all forms

## Architecture Decisions

1. **Why `payment_method` enum instead of boolean?**

    - Supports multiple payment types (not just bilyet vs non-bilyet)
    - Extensible for future payment methods
    - Better reporting and analytics

2. **Why `bilyet_id` nullable?**

    - Not all installments paid via bilyet
    - Historical data compatibility
    - Supports multiple payment workflows

3. **Why separate `purpose` field in bilyets?**

    - Distinguish loan payments from operational expenses
    - Better categorization in reports
    - Optional loan_id allows flexibility

4. **Why service layer?**
    - Centralized business logic
    - Reusable across controllers
    - Easier testing and validation
    - Transaction management

## Contributors

-   AI Assistant (Comprehensive Implementation)
-   Date: October 23, 2025
