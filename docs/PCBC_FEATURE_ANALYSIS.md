# PCBC (Petty Cash Balance Control) Feature Analysis

## Overview

PCBC is a cash counting and reconciliation system designed to track physical cash (paper money and coins) in Indonesian Rupiah. The system allows cashiers to record detailed cash counts, compare them against system amounts and SAP amounts, and generate printable documents for audit purposes.

## Feature Components

### 1. **Dashboard** (`/cashier/pcbc?page=dashboard`)
- Displays uploaded PCBC PDF files organized by project and month
- Shows visual indicators (green circles) for uploaded files
- Allows filtering by year (2024, 2025, 2026)
- Provides quick overview of document compliance status

### 2. **File Upload** (`/cashier/pcbc?page=upload`)
- Allows uploading PDF documents related to PCBC
- Stores files in `public/dokumens/` directory
- Links documents to projects and dates
- Uses `Dokumen` model with `type='pcbc'`

### 3. **PCBC Record Management**
- **Create** (`/cashier/pcbc/create`): Create new PCBC records with detailed cash counting
- **List** (`/cashier/pcbc?page=list`): View user's PCBC records in DataTable
- **Edit** (`/cashier/pcbc/{id}/edit`): Modify existing PCBC records
- **Print** (`/cashier/pcbc/{id}/print`): Generate printable PCBC document
- **Delete** (`/cashier/pcbc/{id}/destroy-pcbc`): Remove PCBC records

## Data Structure

### Cash Denominations Tracked

**Paper Money (Kertas):**
- Rp 100,000 (kertas_100rb)
- Rp 50,000 (kertas_50rb)
- Rp 20,000 (kertas_20rb)
- Rp 10,000 (kertas_10rb)
- Rp 5,000 (kertas_5rb)
- Rp 2,000 (kertas_2rb)
- Rp 1,000 (kertas_1rb)
- Rp 500 (kertas_500)
- Rp 100 (kertas_100)

**Coins (Logam):**
- Rp 1,000 (logam_1rb)
- Rp 500 (logam_500)
- Rp 200 (logam_200)
- Rp 100 (logam_100)
- Rp 50 (logam_50)
- Rp 25 (logam_25)

### Amount Fields
- **system_amount**: Amount from accounting system (manual entry)
- **fisik_amount**: Calculated from physical cash denominations (auto-calculated)
- **sap_amount**: Amount from SAP system (manual entry)

### Approval Workflow
- **pemeriksa1**: First checker (required)
- **pemeriksa2**: Second checker (optional)
- **approved_by**: Approver (nullable but shown as required in UI)

## Document Numbering

PCBC documents use auto-generated numbers via `DocumentNumberController`:
- Format: `YY09PPNNNNN`
  - `YY`: 2-digit year (e.g., 25 for 2025)
  - `09`: Document code for PCBC
  - `PP`: Project code (last 2 digits)
  - `NNNNN`: Sequential number (5 digits, zero-padded)

Example: `2509010001` = PCBC #0001 for project 0001 in 2025

## Current Implementation Strengths

1. **Well-organized controller structure** with private helper methods
2. **Database transactions** for data integrity
3. **Real-time calculation** of physical amounts in frontend
4. **Role-based access control** for viewing records
5. **Comprehensive print view** with proper formatting
6. **Document number generation** integrated with system-wide numbering

## Issues and Recommendations

### 🔴 Critical Issues

#### 1. **Code Duplication: Identical Calculation Methods**
**Issue**: `calculateFisikAmount()` and `calculatePhysicalAmount()` are identical but separate methods.

**Location**: Lines 279-296 and 414-431 in `PcbcController.php`

**Recommendation**:
```php
// Consolidate into single method
private function calculateFisikAmount(Request $request)
{
    return ($request->kertas_100rb * 100000) +
        ($request->kertas_50rb * 50000) +
        // ... rest of calculation
}

// Use same method in both store() and update_pcbc()
```

#### 2. **Missing File Deletion on Record Deletion**
**Issue**: `destroy()` method deletes `Dokumen` record but doesn't remove physical file from disk.

**Location**: Line 163-169 in `PcbcController.php`

**Recommendation**:
```php
public function destroy($id)
{
    $dokumen = Dokumen::find($id);
    
    // Delete physical file
    if ($dokumen->filename1 && file_exists(public_path('dokumens/' . $dokumen->filename1))) {
        unlink(public_path('dokumens/' . $dokumen->filename1));
    }
    
    $dokumen->delete();
    return redirect()->back()->with('success', 'File deleted successfully.');
}
```

#### 3. **Missing Authorization Checks**
**Issue**: `update_pcbc()` and `destroy_pcbc()` don't verify user permissions or ownership.

**Recommendation**:
```php
public function update_pcbc(Request $request, $id)
{
    $pcbc = Pcbc::findOrFail($id);
    
    // Check authorization
    if (!auth()->user()->hasAnyRole(['superadmin', 'admin', 'cashier']) 
        && $pcbc->created_by !== auth()->id()) {
        abort(403, 'Unauthorized action.');
    }
    
    // ... rest of method
}
```

#### 4. **Validation Inconsistency**
**Issue**: `approved_by` is marked as required in view but nullable in validation.

**Location**: Line 118-124 in `create.blade.php` vs Line 270 in `PcbcController.php`

**Recommendation**: Align validation rules with UI requirements:
```php
'approved_by' => 'required|string', // Make consistent with UI
```

### 🟡 Important Improvements

#### 5. **Hardcoded Years Array**
**Issue**: Years are hardcoded in controller constructor.

**Location**: Line 21 in `PcbcController.php`

**Recommendation**:
```php
protected function getAvailableYears()
{
    $currentYear = (int) date('Y');
    return range($currentYear - 2, $currentYear + 1); // Dynamic range
}
```

#### 6. **Missing Eager Loading**
**Issue**: `data()` method may cause N+1 queries when accessing relationships.

**Location**: Line 171-190 in `PcbcController.php`

**Recommendation**:
```php
$dokumens = $query->with(['createdBy', 'project'])->get();
```

#### 7. **No Soft Deletes**
**Issue**: Records are permanently deleted without recovery option.

**Recommendation**:
- Add `SoftDeletes` trait to `Pcbc` model
- Update migration to add `deleted_at` column
- Modify delete methods to use soft delete

#### 8. **Missing Audit Trail**
**Issue**: No tracking of who modified records and when.

**Recommendation**:
- Add `updated_by` field to track modifier
- Consider implementing audit log system similar to Bilyet module
- Track changes to critical fields (amounts, dates)

#### 9. **Inconsistent Method Naming**
**Issue**: Mix of snake_case (`update_pcbc`, `destroy_pcbc`) and camelCase (`updateFile`).

**Recommendation**: Standardize to Laravel conventions:
- Use camelCase for all methods: `updatePcbc()`, `destroyPcbc()`
- Or use resource controller methods: `update()`, `destroy()`

#### 10. **Missing Model Relationships**
**Issue**: `Pcbc` model has minimal relationships defined.

**Recommendation**:
```php
// In Pcbc model
public function project()
{
    return $this->belongsTo(Project::class, 'project', 'code');
}

public function updatedBy()
{
    return $this->belongsTo(User::class, 'updated_by');
}
```

### 🟢 Nice-to-Have Enhancements

#### 11. **Amount Validation**
**Recommendation**: Add validation to ensure physical amount matches calculated amount:
```php
$calculatedAmount = $this->calculateFisikAmount($request);
if (abs($calculatedAmount - $request->fisik_amount) > 0.01) {
    return back()->withErrors(['fisik_amount' => 'Physical amount mismatch']);
}
```

#### 12. **Variance Calculation**
**Recommendation**: Add automatic variance calculation:
```php
$variance = $pcbc->system_amount - $pcbc->fisik_amount;
$sap_variance = $pcbc->sap_amount - $pcbc->fisik_amount;
```

#### 13. **Bulk Operations**
**Recommendation**: Add ability to bulk delete or export PCBC records.

#### 14. **Advanced Filtering**
**Recommendation**: Add filters for date range, project, amount range in list view.

#### 15. **Export Functionality**
**Recommendation**: Add Excel/PDF export for PCBC records.

#### 16. **Dashboard Statistics**
**Recommendation**: Add summary statistics to dashboard:
- Total PCBC records per month
- Variance trends
- Missing documents alerts

#### 17. **Email Notifications**
**Recommendation**: Notify approvers when PCBC is submitted for approval.

#### 18. **Mobile Responsiveness**
**Recommendation**: Improve mobile experience for cash counting interface.

## Code Quality Recommendations

### 1. **Extract Business Logic to Service Class**
Create `PcbcService` to handle:
- Amount calculations
- Document number generation
- Validation logic
- File operations

### 2. **Use Form Request Classes**
Replace inline validation with dedicated Form Request classes:
- `StorePcbcRequest`
- `UpdatePcbcRequest`

### 3. **Add Type Hints and Return Types**
```php
private function calculateFisikAmount(Request $request): float
{
    // ...
}
```

### 4. **Improve Error Handling**
```php
try {
    // operation
} catch (FileException $e) {
    Log::error('PCBC file operation failed', ['error' => $e->getMessage()]);
    return back()->withErrors(['file' => 'File operation failed']);
} catch (\Exception $e) {
    Log::error('PCBC operation failed', ['error' => $e->getMessage()]);
    return back()->withErrors(['general' => 'Operation failed']);
}
```

### 5. **Add Unit Tests**
Create tests for:
- Amount calculations
- Document number generation
- Validation rules
- Authorization checks

## Security Recommendations

1. **File Upload Security**:
   - Validate file size limits
   - Scan for malware
   - Store files outside public directory
   - Use unique filenames

2. **Authorization**:
   - Implement policy classes for fine-grained permissions
   - Check ownership before allowing edits/deletes

3. **Input Sanitization**:
   - Sanitize all text inputs
   - Validate numeric inputs strictly

## Performance Recommendations

1. **Database Indexing**:
   ```php
   // Add indexes in migration
   $table->index(['project', 'pcbc_date']);
   $table->index('created_by');
   $table->index('pcbc_date');
   ```

2. **Query Optimization**:
   - Use eager loading for relationships
   - Add pagination to large datasets
   - Cache frequently accessed data

3. **File Storage**:
   - Consider using cloud storage (S3) for scalability
   - Implement file compression

## Summary

The PCBC feature is well-structured but has several areas for improvement. The most critical issues are code duplication, missing file deletion, and authorization gaps. Implementing the recommended changes will improve code maintainability, security, and user experience.

**Priority Actions**:
1. ✅ Consolidate duplicate calculation methods
2. ✅ Add file deletion on record deletion
3. ✅ Implement authorization checks
4. ✅ Fix validation inconsistencies
5. ✅ Add eager loading to prevent N+1 queries
6. ✅ Consider soft deletes for data recovery
7. ✅ Add audit trail for compliance
