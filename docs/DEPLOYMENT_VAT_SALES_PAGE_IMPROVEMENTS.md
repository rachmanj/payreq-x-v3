# Deployment Manual: VAT Sales Page Improvements

## Overview

This document provides step-by-step instructions for deploying the VAT Sales Page Improvements feature. This feature enhances the VAT Sales pages by:

1. **Incomplete Tab**: Shows documents NOT yet posted to SAP B1, with "Submit to SAP" button
2. **Complete Tab**: Shows documents already posted to SAP B1, displaying AR Invoice DocNum and JE Num columns

## Feature Summary

### Changes Made

1. **Controller Logic Update** (`VatController::data()`)
   - Changed filtering from `doc_num`/`faktur_no` to `sap_ar_doc_num`
   - Incomplete tab: Shows documents where `sap_ar_doc_num IS NULL`
   - Complete tab: Shows documents where `sap_ar_doc_num IS NOT NULL`
   - Added `sap_ar_doc_num` and `sap_je_num` columns to DataTable response

2. **View Updates**
   - **Incomplete Action View**: Added "Submit to SAP" button
   - **Complete View**: Removed "DocNum" column, added "AR Invoice DocNum" and "JE Num" columns
   - **Complete Action View**: Removed "Submit to SAP" button (already submitted)

## Prerequisites

- Laravel 11+ application
- MySQL database
- SAP B1 Service Layer API access
- Existing VAT/Sales faktur functionality

## Database Migrations

### Required Migrations

The following migrations must be run (in order):

1. **Add SAP Tracking Fields to Fakturs Table**
   ```bash
   php artisan migrate
   ```
   Migration file: `database/migrations/2026_01_08_025744_add_sap_tracking_fields_to_fakturs_table.php`
   
   This migration adds:
   - `sap_ar_doc_num` - AR Invoice document number from SAP B1
   - `sap_ar_doc_entry` - AR Invoice document entry from SAP B1
   - `sap_je_num` - Journal Entry number from SAP B1
   - `sap_je_doc_entry` - Journal Entry document entry from SAP B1
   - `sap_submission_status` - Status of SAP submission (pending, ar_created, je_created, completed, failed)
   - `sap_submission_attempts` - Number of submission attempts
   - `sap_submission_error` - Error message if submission failed
   - `sap_submitted_at` - Timestamp when submitted
   - `sap_submitted_by` - User ID who submitted
   - `revenue_account_code` - Revenue account code (41101 or 41201)
   - `project` - Project code for Journal Entry
   - `wtax_amount` - Withholding tax amount

2. **Add Faktur Support to SAP Submission Logs Table**
   ```bash
   php artisan migrate
   ```
   Migration file: `database/migrations/2026_01_08_025755_add_faktur_support_to_sap_submission_logs_table.php`
   
   This migration adds:
   - `faktur_id` - Foreign key to fakturs table
   - `document_type` - Type of document (ar_invoice, journal_entry)
   - `sap_doc_num` - SAP document number
   - `sap_doc_entry` - SAP document entry
   - `sap_error` - SAP error message
   - `submitted_by` - User ID who submitted

3. **Add JE Dates to Fakturs Table**
   ```bash
   php artisan migrate
   ```
   Migration file: `database/migrations/2026_01_12_011006_add_je_dates_to_fakturs_table.php`
   
   This migration adds:
   - `je_posting_date` - Journal Entry posting date
   - `je_tax_date` - Journal Entry tax date
   - `je_due_date` - Journal Entry due date

### Verify Migrations

After running migrations, verify the columns exist:

```sql
DESCRIBE fakturs;
DESCRIBE sap_submission_logs;
```

Expected columns in `fakturs`:
- `sap_ar_doc_num`
- `sap_je_num`
- `sap_submission_status`
- `je_posting_date`
- `je_tax_date`
- `je_due_date`

## Environment Configuration (.env)

### Required SAP B1 Configuration

Add or update the following variables in your `.env` file:

```env
# SAP B1 Service Layer Configuration
SAP_SERVER_URL=https://your-sap-server:50000/b1s/v1/
SAP_DB_NAME=your_database_name
SAP_USER=your_sap_username
SAP_PASSWORD=your_sap_password

# AR Invoice Defaults
SAP_AR_INVOICE_DEFAULT_PAYMENT_TERMS=15
SAP_AR_INVOICE_DEFAULT_REVENUE_ACCOUNT=41101
SAP_AR_INVOICE_DEFAULT_AR_ACCOUNT=491
SAP_AR_INVOICE_DEFAULT_ITEM_CODE=SERVICE
SAP_AR_INVOICE_DEFAULT_DEPARTMENT_CODE=60
SAP_AR_INVOICE_DEFAULT_WTAX_CODE=1019
SAP_AR_INVOICE_WTAX_PERCENTAGE=2

# Faktur Pajak Fields
SAP_AR_INVOICE_AUTHORIZED_NAME_INVOICE=YUWANA
SAP_AR_INVOICE_AUTHORIZED_NAME_FP=M REZA
SAP_AR_INVOICE_KODE_TRANSAKSI_FP=01

# Bank Account Information
SAP_AR_INVOICE_BANK_NAME_USD=MANDIRIBPN4
SAP_AR_INVOICE_BANK_ACCOUNT_USD=149-00-00001158
SAP_AR_INVOICE_BANK_NAME_IDR=MANDIRIBPN5
SAP_AR_INVOICE_BANK_ACCOUNT_IDR=149-00-02222257
```

### Configuration Notes

- **SAP_AR_INVOICE_DEFAULT_AR_ACCOUNT**: Set to `491` (Perantara Pendapatan Kontrak) - must be a sales-type revenue account
- **SAP_AR_INVOICE_DEFAULT_WTAX_CODE**: Set to your withholding tax code (e.g., `1019` for 2% WTax)
- **SAP_AR_INVOICE_WTAX_PERCENTAGE**: Default is `2` (2%)
- **SAP_AR_INVOICE_DEFAULT_REVENUE_ACCOUNT**: Set to `41101` or `41201` based on your chart of accounts

### Clear Configuration Cache

After updating `.env`, clear the configuration cache:

```bash
php artisan config:clear
php artisan config:cache
```

## Database Seeding

### Required Seeder: AR Invoice Permissions

Run the seeder to create the required permission:

```bash
php artisan db:seed --class=ArInvoicePermissionsSeeder
```

This seeder:
- Creates the `submit-sap-ar-invoice` permission
- Assigns the permission to the `superadmin` role

### Manual Permission Assignment (Optional)

If you need to assign the permission to other roles, you can do so via:

1. **Laravel Tinker**:
   ```bash
   php artisan tinker
   ```
   ```php
   $role = \Spatie\Permission\Models\Role::where('name', 'your-role-name')->first();
   $permission = \Spatie\Permission\Models\Permission::where('name', 'submit-sap-ar-invoice')->first();
   $role->givePermissionTo($permission);
   ```

2. **Database Query**:
   ```sql
   INSERT INTO role_has_permissions (permission_id, role_id)
   SELECT p.id, r.id
   FROM permissions p, roles r
   WHERE p.name = 'submit-sap-ar-invoice'
   AND r.name = 'your-role-name';
   ```

## Code Deployment

### Files Modified

1. **Controller**
   - `app/Http/Controllers/Accounting/VatController.php`
     - Updated `data()` method filtering logic
     - Added `sap_ar_doc_num` and `sap_je_num` columns

2. **Views**
   - `resources/views/accounting/vat/ar/incomplete.blade.php` (no changes needed)
   - `resources/views/accounting/vat/ar/complete.blade.php` (updated columns)
   - `resources/views/accounting/vat/ar/action.blade.php` (added Submit button)
   - `resources/views/accounting/vat/ar/action_complete.blade.php` (removed Submit button)

### Deployment Steps

1. **Pull Latest Code**
   ```bash
   git pull origin main
   ```

2. **Install Dependencies** (if any new packages)
   ```bash
   composer install --no-dev --optimize-autoloader
   ```

3. **Run Migrations**
   ```bash
   php artisan migrate
   ```

4. **Run Seeders**
   ```bash
   php artisan db:seed --class=ArInvoicePermissionsSeeder
   ```

5. **Clear Caches**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   php artisan view:clear
   php artisan route:clear
   ```

6. **Optimize (Production Only)**
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

## Post-Deployment Verification

### 1. Check Database Structure

Verify all columns exist:

```sql
-- Check fakturs table
SHOW COLUMNS FROM fakturs LIKE 'sap_%';
SHOW COLUMNS FROM fakturs LIKE 'je_%';

-- Check sap_submission_logs table
SHOW COLUMNS FROM sap_submission_logs LIKE 'faktur%';
```

### 2. Verify Permissions

Check that the permission exists and is assigned:

```sql
SELECT * FROM permissions WHERE name = 'submit-sap-ar-invoice';
SELECT r.name, p.name 
FROM roles r
JOIN role_has_permissions rhp ON r.id = rhp.role_id
JOIN permissions p ON rhp.permission_id = p.id
WHERE p.name = 'submit-sap-ar-invoice';
```

### 3. Test Functionality

1. **Access Incomplete Tab**
   - Navigate to: `/accounting/vat?page=sales&status=incomplete`
   - Verify: Documents without `sap_ar_doc_num` are shown
   - Verify: "Submit to SAP" button appears in action column

2. **Test Submission**
   - Click "Submit to SAP" on a document
   - Complete the submission process
   - Verify: Document moves to Complete tab after successful submission

3. **Access Complete Tab**
   - Navigate to: `/accounting/vat?page=sales&status=complete`
   - Verify: Documents with `sap_ar_doc_num` are shown
   - Verify: "AR Invoice DocNum" column displays `sap_ar_doc_num`
   - Verify: "JE Num" column displays `sap_je_num`
   - Verify: "DocNum" column is removed
   - Verify: "Submit to SAP" button is NOT shown (already submitted)

### 4. Check Logs

Monitor application logs for any errors:

```bash
tail -f storage/logs/laravel.log
```

## Troubleshooting

### Issue: Documents Not Showing in Correct Tab

**Problem**: Documents appear in wrong tab (incomplete vs complete)

**Solution**:
1. Check if `sap_ar_doc_num` is set correctly:
   ```sql
   SELECT id, invoice_no, sap_ar_doc_num FROM fakturs WHERE type = 'sales' LIMIT 10;
   ```
2. Verify filtering logic in `VatController::data()` method
3. Clear cache: `php artisan cache:clear`

### Issue: "Submit to SAP" Button Not Appearing

**Problem**: Button doesn't show in incomplete tab

**Solution**:
1. Check if user has permission: `submit-sap-ar-invoice`
2. Verify document has `faktur_no` and `faktur_date` set
3. Check if `sap_ar_doc_num` is NULL (should be NULL for incomplete)
4. Clear view cache: `php artisan view:clear`

### Issue: Columns Not Displaying

**Problem**: AR Invoice DocNum or JE Num columns not showing

**Solution**:
1. Verify DataTable columns configuration in `complete.blade.php`
2. Check browser console for JavaScript errors
3. Verify `sap_ar_doc_num` and `sap_je_num` are returned in DataTable response
4. Clear browser cache and reload

### Issue: Permission Denied Error

**Problem**: 403 error when trying to submit

**Solution**:
1. Run the seeder: `php artisan db:seed --class=ArInvoicePermissionsSeeder`
2. Assign permission to user's role manually
3. Verify user is logged in and has correct role

### Issue: Migration Errors

**Problem**: Migration fails with column already exists

**Solution**:
1. Check if columns already exist:
   ```sql
   DESCRIBE fakturs;
   ```
2. Migrations use `if (!Schema::hasColumn())` checks, so they're safe to re-run
3. If issue persists, check migration file for syntax errors

## Rollback Procedure

If you need to rollback this feature:

1. **Revert Code Changes**
   ```bash
   git revert <commit-hash>
   ```

2. **Clear Caches**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   php artisan view:clear
   ```

3. **Note**: Database migrations are NOT rolled back automatically. The new columns remain in the database but won't be used by the reverted code.

## Support

For issues or questions:
1. Check application logs: `storage/logs/laravel.log`
2. Check SAP submission logs: `sap_submission_logs` table
3. Verify SAP B1 Service Layer connectivity
4. Contact development team

## Related Documentation

- `docs/SAP_B1_AR_INVOICE_INTEGRATION_FINAL_PLAN.md` - Complete SAP B1 AR Invoice integration documentation
- `docs/SAP_B1_AR_INVOICE_INTEGRATION_UPDATED.md` - Updated integration details
- `docs/architecture.md` - System architecture documentation

## Version History

- **v1.0** (2026-01-XX): Initial deployment
  - Incomplete/Complete tab filtering based on `sap_ar_doc_num`
  - Added "Submit to SAP" button in incomplete tab
  - Updated complete tab columns (AR Invoice DocNum, JE Num)
