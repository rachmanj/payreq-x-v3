# Fitur CRUD Kurs (Currency Exchange Rate)

## Overview

Fitur ini memungkinkan pengguna untuk mengelola kurs mata uang dengan berbagai metode input dan pengelolaan data yang fleksibel.

## Lokasi Menu

-   **Menu Utama**: Accounting
-   **Sub Menu**: Kurs / Exchange Rate

## Fitur Utama

### 1. CRUD Operations

#### Create (Tambah Kurs)

-   **Input Manual**

    -   Form input dengan field:
        -   Currency From (dropdown)
        -   Currency To (dropdown)
        -   Exchange Rate (decimal)
        -   Date Range (Start Date - End Date)
        -   Created By (auto-filled dengan user login)
    -   Sistem akan generate record per tanggal dalam range yang dipilih
    -   Validasi: tidak boleh ada duplikasi currency pair pada tanggal yang sama

-   **Input via Excel Import**
    -   Template Excel yang disediakan dengan kolom:
        -   Currency From
        -   Currency To
        -   Exchange Rate
        -   Date
        -   Created By (optional, jika kosong akan diisi dengan user yang import)
    -   Validasi data sebelum import
    -   Preview data sebelum konfirmasi import
    -   Error handling untuk data yang tidak valid

#### Read (Lihat/Cari Kurs)

-   **List View**
    -   Tabel dengan kolom:
        -   Currency From
        -   Currency To
        -   Exchange Rate
        -   Date
        -   Created By
        -   Created At
        -   Updated At
        -   Actions (Edit/Delete)
-   **Filter & Search**
    -   Filter berdasarkan:
        -   Currency From
        -   Currency To
        -   Date Range
        -   Created By
    -   Search global
    -   Export to Excel

#### Update (Edit Kurs)

-   **Edit Single Record**
    -   Form edit untuk record individual
    -   Tracking perubahan (audit trail)
-   **Bulk Update with Date Range**
    -   Select multiple records berdasarkan currency pair
    -   Update rate untuk range tanggal tertentu
    -   Konfirmasi sebelum bulk update
    -   Log semua perubahan

#### Delete (Hapus Kurs)

-   **Soft Delete**
    -   Record tidak benar-benar dihapus, hanya di-mark sebagai deleted
    -   Dapat di-restore jika diperlukan
-   **Bulk Delete**
    -   Delete multiple records sekaligus
    -   Konfirmasi sebelum delete

### 2. Database Schema

```sql
CREATE TABLE exchange_rates (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    currency_from VARCHAR(3) NOT NULL,
    currency_to VARCHAR(3) NOT NULL,
    exchange_rate DECIMAL(15,6) NOT NULL,
    effective_date DATE NOT NULL,
    created_by BIGINT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by BIGINT NULL,
    deleted_at TIMESTAMP NULL,
    deleted_by BIGINT NULL,

    UNIQUE KEY unique_currency_date (currency_from, currency_to, effective_date, deleted_at),
    FOREIGN KEY (created_by) REFERENCES users(id),
    FOREIGN KEY (updated_by) REFERENCES users(id),
    FOREIGN KEY (deleted_by) REFERENCES users(id),

    INDEX idx_currency_pair (currency_from, currency_to),
    INDEX idx_effective_date (effective_date),
    INDEX idx_created_by (created_by)
);
```

### 3. Business Rules

#### Validasi Input

-   Currency From dan Currency To tidak boleh sama
-   Exchange Rate harus > 0
-   Effective Date tidak boleh di masa depan (opsional, tergantung kebutuhan bisnis)
-   Currency code harus valid (ISO 4217)

#### Audit Trail

-   Setiap perubahan data harus tercatat
-   Log meliputi: field yang berubah, nilai lama, nilai baru, user yang mengubah, timestamp

#### Permission & Security

-   Role-based access control
-   Permission untuk Create, Read, Update, Delete
-   User hanya bisa melihat/edit data yang dia buat (opsional)

### 4. User Interface Specifications

#### List Page

```
+----------------------------------------------------------+
|  Exchange Rates Management                    [+ Add New] |
+----------------------------------------------------------+
| Filters: [Currency From ▼] [Currency To ▼] [Date Range] |
|          [Created By ▼] [Search...] [Apply] [Reset]     |
+----------------------------------------------------------+
| [☐] From | To  | Rate      | Date       | Created By    |
| [☐] USD  | IDR | 15,750.00 | 2024-01-15 | John Doe     |
| [☐] EUR  | IDR | 17,250.00 | 2024-01-15 | Jane Smith   |
| [☐] SGD  | IDR | 11,500.00 | 2024-01-15 | John Doe     |
+----------------------------------------------------------+
| [Bulk Actions ▼] [Export Excel] [Import Excel]          |
+----------------------------------------------------------+
```

#### Add/Edit Form

```
+------------------------------------------+
|  Add Exchange Rate                   [x] |
+------------------------------------------+
| Currency From: [USD ▼]                   |
| Currency To:   [IDR ▼]                   |
| Exchange Rate: [15750.00]                |
| Date Range:    [2024-01-15] to [2024-01-31] |
|                                          |
| Note: This will create 17 records       |
| (one for each date in the range)        |
|                                          |
| [Save] [Cancel]                          |
+------------------------------------------+
```

#### Excel Import Dialog

```
+------------------------------------------+
|  Import Exchange Rates from Excel   [x] |
+------------------------------------------+
| 1. Download Template: [Download Template] |
| 2. Upload File: [Choose File] [Browse]   |
| 3. Preview Data:                         |
|    [Data preview table here]             |
| 4. Import: [Import Data] [Cancel]        |
+------------------------------------------+
```

### 5. API Endpoints

#### REST API Structure

```
GET    /api/exchange-rates              // List all rates with filters
POST   /api/exchange-rates              // Create new rate(s)
GET    /api/exchange-rates/{id}         // Get specific rate
PUT    /api/exchange-rates/{id}         // Update specific rate
DELETE /api/exchange-rates/{id}         // Delete specific rate

POST   /api/exchange-rates/bulk-create  // Bulk create with date range
PUT    /api/exchange-rates/bulk-update  // Bulk update with date range
DELETE /api/exchange-rates/bulk-delete  // Bulk delete

POST   /api/exchange-rates/import       // Import from Excel
GET    /api/exchange-rates/export       // Export to Excel
GET    /api/exchange-rates/template     // Download Excel template
```

### 6. Technical Requirements

#### Frontend

-   Responsive design (mobile friendly)
-   Real-time validation
-   Async file upload untuk Excel import
-   Progress indicator untuk bulk operations
-   Confirmation dialogs untuk destructive actions

#### Backend

-   Input validation dan sanitization
-   Transaction handling untuk bulk operations
-   Rate limiting untuk API calls
-   Logging dan monitoring
-   Error handling yang comprehensive

#### Performance

-   Pagination untuk large datasets
-   Indexing pada database untuk query optimization
-   Caching untuk currency codes dan user data
-   Async processing untuk import/export operations

### 7. Testing Requirements

#### Unit Tests

-   Validation logic
-   Business rules
-   Database operations
-   API endpoints

#### Integration Tests

-   Excel import/export functionality
-   Bulk operations
-   User permission handling

#### User Acceptance Tests

-   End-to-end user workflows
-   Cross-browser compatibility
-   Mobile responsiveness

### 8. Deployment & Migration

#### Database Migration

```sql
-- Migration script untuk create table dan indexes
-- Data migration dari sistem lama (jika ada)
-- Rollback scripts
```

#### Feature Flags

-   Gradual rollout capability
-   A/B testing untuk UI improvements
-   Quick disable mechanism jika ada issues

### 9. Future Enhancements

#### Phase 2 Features

-   Auto-update rates dari external API (Bank Indonesia, Yahoo Finance, etc.)
-   Rate change notifications
-   Historical rate analysis dan reporting
-   Multi-currency conversion calculator
-   Rate approval workflow
-   Integration dengan sistem accounting lainnya

#### Analytics & Reporting

-   Usage statistics
-   Rate volatility reports
-   User activity reports
-   Performance monitoring dashboard

---

## Implementation Timeline

| Phase   | Duration | Description                           |
| ------- | -------- | ------------------------------------- |
| Phase 1 | 2 weeks  | Basic CRUD operations + Manual input  |
| Phase 2 | 1 week   | Excel import/export functionality     |
| Phase 3 | 1 week   | Bulk operations + Date range features |
| Phase 4 | 1 week   | UI/UX polish + Testing                |
| Phase 5 | 1 week   | Deployment + Documentation            |

**Total Estimated Time: 6 weeks**

---

## Acceptance Criteria

-   [ ] User dapat menambah kurs dengan input manual
-   [ ] User dapat menambah kurs dengan import Excel
-   [ ] User dapat input dengan range date dan generate record per tanggal
-   [ ] User dapat update kurs dengan range date
-   [ ] User dapat melihat, edit, dan hapus kurs
-   [ ] System mencatat created_by untuk setiap record
-   [ ] Menu Kurs terintegrasi dalam menu Accounting
-   [ ] Validasi input berfungsi dengan baik
-   [ ] Bulk operations berjalan dengan benar
-   [ ] Export/Import Excel berfungsi
-   [ ] UI responsive di berbagai device
-   [ ] Performance acceptable untuk large datasets
