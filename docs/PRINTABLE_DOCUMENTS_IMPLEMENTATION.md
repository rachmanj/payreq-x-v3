# Printable Documents Implementation Guide

## Overview

This document outlines the implementation of printable document management for Payment Request documents of type **Reimburse** and their **Realizations**.

## Features Implemented

### 1. Admin Side - Printable Document Management

-   **Location**: Admin Menu → Printable Documents
-   **Access**: Requires `akses_admin` permission
-   **Functionality**:
    -   View all reimburse PayReqs and realizations
    -   Toggle printable status individually
    -   Bulk enable/disable printable status
    -   Real-time status updates via AJAX

### 2. User Side - Enhanced Histories with Print Functionality

-   **Location**: My PayReqs → Histories
-   **Functionality**:
    -   Print Status column showing current printable status
    -   Print buttons for printable documents
    -   Separate print buttons for PayReq and Realization
    -   Visual indicators for print availability

## Technical Implementation

### Database Schema

Both `payreqs` and `realizations` tables have a `printable` boolean field:

```sql
$table->boolean('printable')->default(false);
```

### Files Created/Modified

#### Admin Controller

-   `app/Http/Controllers/Admin/PrintableDocumentController.php`

#### Admin Views

-   `resources/views/admin/printable-documents/index.blade.php`
-   `resources/views/admin/printable-documents/action.blade.php`

#### Routes

-   `routes/admin.php` (new file)
-   Updated `routes/web.php` to include admin routes

#### User Histories Enhancement

-   Updated `app/Http/Controllers/UserPayreqHistoriesController.php`
-   Updated `resources/views/user-payreqs/histories/index.blade.php`
-   Updated `resources/views/user-payreqs/histories/action.blade.php`

#### Menu Updates

-   Updated `resources/views/templates/partials/menu/admin.blade.php`

## API Endpoints

### Admin Endpoints

-   `GET /admin/printable-documents` - View management page
-   `GET /admin/printable-documents/data` - DataTables AJAX data
-   `POST /admin/printable-documents/update` - Update single printable status
-   `POST /admin/printable-documents/bulk-update` - Bulk update printable status

### User Endpoints

-   Existing endpoints for histories and printing remain unchanged
-   Enhanced DataTables response includes print status information

## Usage Instructions

### For Administrators

1. Navigate to Admin → Printable Documents
2. View list of reimburse PayReqs and their realizations
3. Use checkboxes to toggle printable status for individual documents
4. Use bulk actions to enable/disable multiple documents
5. Preview print functionality using action buttons

### For Users

1. Navigate to My PayReqs → Histories
2. View Print Status column to see current printable status
3. Use print buttons to print documents (only visible if printable)
4. PayReq and Realization have separate print buttons if both are printable

## Security Considerations

-   Admin access controlled by `akses_admin` permission
-   Users can only print their own documents
-   Printable status changes are logged via Toastr notifications
-   CSRF protection on all update endpoints

## Future Enhancements

-   Add audit trail for printable status changes
-   Email notifications when printable status changes
-   Bulk actions with date range filters
-   Export functionality for printable documents list

## Testing Scenarios

1. **Admin Functionality**:

    - Toggle individual printable status
    - Bulk enable/disable operations
    - Permission checks for admin access

2. **User Functionality**:

    - Print buttons visibility based on printable status
    - Print functionality for both PayReq and Realization
    - Print status indicators in histories

3. **Edge Cases**:
    - Documents without realizations
    - Non-reimburse type documents
    - Unauthorized access attempts
