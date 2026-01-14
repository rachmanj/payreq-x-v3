# VAT Sales Page Improvements - Deployment Checklist

## Pre-Deployment

- [ ] Review code changes in pull request/commit
- [ ] Backup database
- [ ] Verify SAP B1 Service Layer is accessible
- [ ] Notify users of scheduled maintenance (if needed)

## Database Migrations

- [ ] Run migration: `2026_01_08_025744_add_sap_tracking_fields_to_fakturs_table.php`
  ```bash
  php artisan migrate
  ```
- [ ] Run migration: `2026_01_08_025755_add_faktur_support_to_sap_submission_logs_table.php`
- [ ] Run migration: `2026_01_12_011006_add_je_dates_to_fakturs_table.php`
- [ ] Verify migrations completed successfully
- [ ] Check database columns exist:
  ```sql
  DESCRIBE fakturs;
  DESCRIBE sap_submission_logs;
  ```

## Environment Configuration

- [ ] Update `.env` file with SAP B1 configuration:
  - [ ] `SAP_SERVER_URL`
  - [ ] `SAP_DB_NAME`
  - [ ] `SAP_USER`
  - [ ] `SAP_PASSWORD`
  - [ ] `SAP_AR_INVOICE_DEFAULT_AR_ACCOUNT=491`
  - [ ] `SAP_AR_INVOICE_DEFAULT_WTAX_CODE=1019`
  - [ ] `SAP_AR_INVOICE_DEFAULT_REVENUE_ACCOUNT=41101`
  - [ ] Other AR Invoice defaults (see full list in deployment manual)

- [ ] Clear configuration cache:
  ```bash
  php artisan config:clear
  php artisan config:cache
  ```

## Database Seeding

- [ ] Run AR Invoice Permissions Seeder:
  ```bash
  php artisan db:seed --class=ArInvoicePermissionsSeeder
  ```
- [ ] Verify permission created:
  ```sql
  SELECT * FROM permissions WHERE name = 'submit-sap-ar-invoice';
  ```
- [ ] Verify permission assigned to superadmin role:
  ```sql
  SELECT r.name, p.name 
  FROM roles r
  JOIN role_has_permissions rhp ON r.id = rhp.role_id
  JOIN permissions p ON rhp.permission_id = p.id
  WHERE p.name = 'submit-sap-ar-invoice';
  ```

## Code Deployment

- [ ] Pull latest code from repository
- [ ] Install dependencies (if any):
  ```bash
  composer install --no-dev --optimize-autoloader
  ```
- [ ] Verify files are updated:
  - [ ] `app/Http/Controllers/Accounting/VatController.php`
  - [ ] `resources/views/accounting/vat/ar/complete.blade.php`
  - [ ] `resources/views/accounting/vat/ar/action.blade.php`
  - [ ] `resources/views/accounting/vat/ar/action_complete.blade.php`

## Cache Management

- [ ] Clear all caches:
  ```bash
  php artisan config:clear
  php artisan cache:clear
  php artisan view:clear
  php artisan route:clear
  ```
- [ ] Optimize for production (if applicable):
  ```bash
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache
  ```

## Post-Deployment Testing

### Incomplete Tab Testing

- [ ] Navigate to: `/accounting/vat?page=sales&status=incomplete`
- [ ] Verify: Page loads without errors
- [ ] Verify: Only documents with `sap_ar_doc_num IS NULL` are shown
- [ ] Verify: "Submit to SAP" button appears in action column
- [ ] Verify: Button links to SAP preview page

### Complete Tab Testing

- [ ] Navigate to: `/accounting/vat?page=sales&status=complete`
- [ ] Verify: Page loads without errors
- [ ] Verify: Only documents with `sap_ar_doc_num IS NOT NULL` are shown
- [ ] Verify: "AR Invoice DocNum" column displays `sap_ar_doc_num` values
- [ ] Verify: "JE Num" column displays `sap_je_num` values
- [ ] Verify: "DocNum" column is removed (not visible)
- [ ] Verify: "Submit to SAP" button is NOT shown

### Submission Flow Testing

- [ ] Select a document from Incomplete tab
- [ ] Click "Submit to SAP" button
- [ ] Verify: SAP preview page loads
- [ ] Complete submission process
- [ ] Verify: Success message appears
- [ ] Verify: Document moves to Complete tab automatically
- [ ] Verify: AR Invoice DocNum and JE Num are displayed in Complete tab

### Permission Testing

- [ ] Test with superadmin user (should have access)
- [ ] Test with user without permission (should see 403 error)
- [ ] Assign permission to test role and verify access

## Verification Queries

Run these SQL queries to verify data integrity:

```sql
-- Check for documents with sap_ar_doc_num
SELECT COUNT(*) as complete_count 
FROM fakturs 
WHERE type = 'sales' 
AND sap_ar_doc_num IS NOT NULL;

-- Check for documents without sap_ar_doc_num
SELECT COUNT(*) as incomplete_count 
FROM fakturs 
WHERE type = 'sales' 
AND sap_ar_doc_num IS NULL;

-- Check submission logs
SELECT COUNT(*) as submission_logs_count
FROM sap_submission_logs
WHERE faktur_id IS NOT NULL;
```

## Monitoring

- [ ] Monitor application logs for errors:
  ```bash
  tail -f storage/logs/laravel.log
  ```
- [ ] Check for any JavaScript console errors in browser
- [ ] Monitor SAP submission success rate
- [ ] Check database performance (if applicable)

## Documentation

- [ ] Update user documentation (if needed)
- [ ] Notify users of new feature availability
- [ ] Document any issues encountered during deployment

## Rollback Plan (If Needed)

- [ ] Revert code changes:
  ```bash
  git revert <commit-hash>
  ```
- [ ] Clear caches:
  ```bash
  php artisan config:clear
  php artisan cache:clear
  php artisan view:clear
  ```
- [ ] Note: Database migrations are NOT rolled back (columns remain)

## Sign-Off

- [ ] Deployment completed by: _________________
- [ ] Date: _________________
- [ ] Tested by: _________________
- [ ] Approved by: _________________

## Notes

_Add any deployment-specific notes or issues encountered here:_

_________________________________________________
_________________________________________________
_________________________________________________
