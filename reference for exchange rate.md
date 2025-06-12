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

-   **Hard Delete**
    -   Record akan dihapus permanen dari database
    -   Konfirmasi wajib sebelum delete
-   **Bulk Delete**
    -   Delete multiple records sekaligus
    -   Konfirmasi sebelum delete

### 2. Database Schema

```sql
-- Master Data Currency
CREATE TABLE currencies (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    currency_code VARCHAR(3) NOT NULL UNIQUE,
    currency_name VARCHAR(100) NOT NULL,
    symbol VARCHAR(10) NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_by BIGINT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by BIGINT NULL,

    FOREIGN KEY (created_by) REFERENCES users(id),
    FOREIGN KEY (updated_by) REFERENCES users(id),

    INDEX idx_currency_code (currency_code),
    INDEX idx_active (is_active)
);

-- Exchange Rates Table
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

    UNIQUE KEY unique_currency_date (currency_from, currency_to, effective_date),
    FOREIGN KEY (created_by) REFERENCES users(id),
    FOREIGN KEY (updated_by) REFERENCES users(id),
    FOREIGN KEY (currency_from) REFERENCES currencies(currency_code),
    FOREIGN KEY (currency_to) REFERENCES currencies(currency_code),

    INDEX idx_currency_pair (currency_from, currency_to),
    INDEX idx_effective_date (effective_date),
    INDEX idx_created_by (created_by)
);

-- Sample Data untuk Currencies
INSERT INTO currencies (currency_code, currency_name, symbol, created_by) VALUES
('IDR', 'Indonesian Rupiah', 'Rp', 1),
('USD', 'US Dollar', '$', 1),
('EUR', 'Euro', '€', 1),
('SGD', 'Singapore Dollar', 'S$', 1),
('JPY', 'Japanese Yen', '¥', 1),
('GBP', 'British Pound', '£', 1),
('AUD', 'Australian Dollar', 'A$', 1),
('CNY', 'Chinese Yuan', '¥', 1),
('KRW', 'South Korean Won', '₩', 1),
('MYR', 'Malaysian Ringgit', 'RM', 1);
```

### 3. Business Rules

#### Validasi Input

-   Currency From dan Currency To tidak boleh sama
-   Exchange Rate harus > 0
-   Effective Date boleh di masa depan
-   Currency code harus valid (ISO 4217)

#### Audit Trail

-   Setiap perubahan data harus tercatat
-   Log meliputi: field yang berubah, nilai lama, nilai baru, user yang mengubah, timestamp

#### Permission & Security

-   Role-based access control
-   Permission untuk Create, Read, Update, Delete
-   User hanya bisa melihat kurs jika memiliki permission untuk akses fitur ini

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

### 5. Technical Requirements

#### Frontend

-   Menggunakan template AdminLTE bawaan aplikasi
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

### 6. Testing Requirements

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

### 7. Deployment & Migration

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

### 8. Future Enhancements

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

### 9. API Endpoints

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

---

## Langkah-Langkah Teknis Development

### 1. Database Setup & Migration

#### 1.1 Create Migration Files

```bash
# Membuat migration untuk table currencies
php artisan make:migration create_currencies_table

# Membuat migration untuk table exchange_rates
php artisan make:migration create_exchange_rates_table
```

#### 1.2 Setup Migration Scripts

-   Copy SQL schema dari dokumentasi ke migration files
-   Pastikan foreign key constraints sudah benar
-   Tambahkan rollback scripts untuk down() method

#### 1.3 Run Migration & Seeding

```bash
# Jalankan migration
php artisan migrate

# Buat seeder untuk currency master data
php artisan make:seeder CurrencySeeder
php artisan db:seed --class=CurrencySeeder
```

### 2. Model & Repository Setup

#### 2.1 Create Eloquent Models

```bash
# Buat model Currency
php artisan make:model Currency

# Buat model ExchangeRate
php artisan make:model ExchangeRate
```

#### 2.2 Setup Model Relationships

-   Currency model: hasMany ExchangeRate
-   ExchangeRate model: belongsTo Currency (for both currency_from & currency_to)
-   Setup fillable, casts, dan validation rules

#### 2.3 Create Repository Pattern (Optional)

```bash
# Buat repository untuk business logic
php artisan make:repository ExchangeRateRepository
```

### 3. Controller Development (Monolith)

#### 3.1 Create Controller

```bash
# Buat controller untuk Exchange Rate (Web Controller)
php artisan make:controller ExchangeRateController --resource

# Buat controller untuk Currency master data
php artisan make:controller CurrencyController --resource
```

#### 3.2 Setup Web Routes

```php
// routes/web.php
Route::middleware(['auth'])->group(function () {
    Route::resource('admin/exchange-rates', ExchangeRateController::class);
    Route::post('admin/exchange-rates/bulk-create', [ExchangeRateController::class, 'bulkCreate'])->name('exchange-rates.bulk-create');
    Route::put('admin/exchange-rates/bulk-update', [ExchangeRateController::class, 'bulkUpdate'])->name('exchange-rates.bulk-update');
    Route::delete('admin/exchange-rates/bulk-delete', [ExchangeRateController::class, 'bulkDelete'])->name('exchange-rates.bulk-delete');
    Route::post('admin/exchange-rates/import', [ExchangeRateController::class, 'import'])->name('exchange-rates.import');
    Route::get('admin/exchange-rates/export', [ExchangeRateController::class, 'export'])->name('exchange-rates.export');
    Route::get('admin/exchange-rates/template', [ExchangeRateController::class, 'downloadTemplate'])->name('exchange-rates.template');
});
```

#### 3.3 Implement Controller Methods

-   index() - Tampilkan halaman list dengan filtering & pagination
-   create() - Tampilkan form create
-   store() - Process form create (single/multiple records)
-   show() - Tampilkan detail record
-   edit() - Tampilkan form edit
-   update() - Process form update
-   destroy() - Delete record dengan redirect
-   bulkCreate() - Create dengan date range
-   bulkUpdate() - Update multiple records
-   bulkDelete() - Delete multiple records
-   import() - Excel import functionality dengan redirect
-   export() - Excel export functionality
-   downloadTemplate() - Download Excel template

### 4. Excel Import/Export Implementation

#### 4.1 Install Laravel Excel

```bash
composer require maatwebsite/excel
php artisan vendor:publish --provider="Maatwebsite\Excel\ExcelServiceProvider" --tag=config
```

#### 4.2 Create Excel Classes

```bash
# Export class
php artisan make:export ExchangeRatesExport --model=ExchangeRate

# Import class
php artisan make:import ExchangeRatesImport --model=ExchangeRate

# Template export
php artisan make:export ExchangeRateTemplateExport
```

#### 4.3 Implement Excel Logic

-   ExchangeRatesExport: untuk export data existing
-   ExchangeRatesImport: untuk import data dari Excel
-   ExchangeRateTemplateExport: untuk download template kosong
-   Tambahkan validasi & error handling pada import

### 5. Frontend Development (AdminLTE)

#### 5.1 Create Blade Templates

```bash
# Buat folder views untuk exchange rates
mkdir resources/views/exchange-rates

# File-file yang perlu dibuat:
# - index.blade.php (list page)
# - create.blade.php (form tambah)
# - edit.blade.php (form edit)
# - import.blade.php (import dialog)
```

#### 5.2 Setup AdminLTE Components

-   Gunakan AdminLTE DataTables untuk list
-   AdminLTE form components untuk input
-   AdminLTE modal untuk import dialog
-   AdminLTE alerts untuk notifikasi

#### 5.3 JavaScript Implementation

```javascript
// public/js/exchange-rates.js
- Form submission dengan validation
- Date range picker implementation
- File upload handling untuk Excel import
- Bulk operations (select all, bulk delete)
- Form validation client-side
- Export functionality dengan form submission
- Confirmation dialogs untuk delete operations
```

#### 5.4 CSS Customization

```css
/* public/css/exchange-rates.css */
- Custom styling untuk form elements
- Responsive table adjustments
- Loading indicators
- Error/success message styling
```

### 6. Menu & Navigation Setup

#### 6.1 Update AdminLTE Sidebar

```php
// config/adminlte.php atau blade template
// Tambah menu item di section Accounting
[
    'text' => 'Accounting',
    'icon' => 'fas fa-calculator',
    'submenu' => [
        [
            'text' => 'Exchange Rates',
            'url' => 'admin/exchange-rates',
            'icon' => 'fas fa-exchange-alt',
        ],
    ],
],
```

#### 6.2 Setup Web Routes

```php
// routes/web.php - Sudah covered di section 3.2
// Routes sudah didefinisikan di Controller Development section
```

### 7. Permission & Security Implementation

#### 7.1 Create Permissions

```bash
# Gunakan Spatie Permission atau sistem permission existing
# Permissions yang diperlukan:
- exchange-rates.view
- exchange-rates.create
- exchange-rates.update
- exchange-rates.delete
- exchange-rates.import
- exchange-rates.export
```

#### 7.2 Setup Middleware & Gates

```php
// Middleware untuk check permission
Route::middleware(['permission:exchange-rates.view'])->group(function () {
    // Routes untuk exchange rates
});

// Gates untuk fine-grained control
Gate::define('manage-exchange-rates', function ($user) {
    return $user->hasPermissionTo('exchange-rates.view');
});
```

#### 7.3 Blade Permission Checks

```php
// Di blade templates
@can('exchange-rates.create')
    <button class="btn btn-primary">Add New</button>
@endcan

@can('exchange-rates.delete')
    <button class="btn btn-danger">Delete</button>
@endcan
```

### 8. Testing Implementation

#### 8.1 Unit Tests

```bash
# Test untuk Models
php artisan make:test CurrencyTest --unit
php artisan make:test ExchangeRateTest --unit

# Test untuk Controllers
php artisan make:test ExchangeRateControllerTest
```

#### 8.2 Feature Tests

```bash
# Test untuk complete workflows
php artisan make:test ExchangeRateCrudTest
php artisan make:test ExchangeRateImportTest
php artisan make:test ExchangeRateBulkOperationsTest
```

#### 8.3 Browser Tests (Laravel Dusk)

```bash
# Install Dusk untuk UI testing
composer require --dev laravel/dusk
php artisan dusk:install

# Buat browser tests
php artisan dusk:make ExchangeRateTest
```

### 9. Validation & Error Handling

#### 9.1 Server-Side Validation

-   Form Request validation untuk semua input
-   Business rule validation (currency pair, date range, etc.)
-   File validation untuk Excel import
-   Rate limiting untuk bulk operations

#### 9.2 Client-Side Validation

```javascript
// jQuery validation atau vanilla JavaScript
- Real-time field validation
- Date range validation
- File type/size validation untuk upload
- Form submission validation
- Confirmation dialogs untuk destructive actions
```

#### 9.3 Error Handling & Logging

```php
// Exception handling
try {
    // Operation
} catch (ValidationException $e) {
    // Handle validation errors
} catch (Exception $e) {
    // Log error dan return user-friendly message
    Log::error('Exchange Rate Error: ' . $e->getMessage());
}
```

### 10. Performance Optimization

#### 10.1 Database Optimization

-   Pastikan indexes sudah optimal
-   Implement query optimization untuk large datasets
-   Database connection pooling jika diperlukan

#### 10.2 Caching Implementation

```php
// Cache currency list
Cache::remember('active_currencies', 3600, function () {
    return Currency::where('is_active', true)->get();
});

// Cache untuk frequent queries
```

#### 10.3 Frontend Optimization

-   Lazy loading untuk large tables
-   Server-side pagination dengan Laravel
-   Debouncing untuk search input
-   Minifikasi JS/CSS assets

### 11. Deployment Preparation

#### 11.1 Environment Setup

```bash
# Production environment variables
EXCHANGE_RATE_IMPORT_MAX_SIZE=10240
EXCHANGE_RATE_BULK_LIMIT=1000
CACHE_DRIVER=redis
```

#### 11.2 Asset Compilation

```bash
# Compile assets untuk production
npm run production

# Optimize autoloader
composer install --optimize-autoloader --no-dev
```

#### 11.3 Migration & Seeding Scripts

```bash
# Production migration command
php artisan migrate --force
php artisan db:seed --class=CurrencySeeder --force
```

### 12. Documentation & Training

#### 12.1 Technical Documentation

-   API documentation dengan Swagger/OpenAPI
-   Database schema documentation
-   Code comments dan PHPDoc

#### 12.2 User Manual

-   Step-by-step user guide
-   Screenshot untuk setiap fitur
-   FAQ dan troubleshooting guide

#### 12.3 Developer Handover

-   Code walkthrough session
-   Architecture explanation
-   Maintenance guidelines

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
